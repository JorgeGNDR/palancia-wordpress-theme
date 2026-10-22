<?php
defined( 'ABSPATH' ) || exit;

/** One query definition for the grid and previous/next product navigation. */
function prs_catalog_query_args( $state ) {
    $visibility = wc_get_product_visibility_term_ids();
    $excluded = [ $visibility['exclude-from-catalog'] ];
    if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items', 'no' ) ) {
        $excluded[] = $visibility['outofstock'];
    }
    $args = [
        'post_type' => 'product', 'post_status' => 'publish',
        'posts_per_page' => -1, 'fields' => 'ids', 'no_found_rows' => true,
        'meta_key' => '_price',
        'orderby' => [ 'meta_value_num' => 'price-asc' === $state['sort'] ? 'ASC' : 'DESC', 'ID' => 'DESC' ],
        'tax_query' => [ 'relation' => 'AND', [
            'taxonomy' => 'product_visibility', 'field' => 'term_taxonomy_id',
            'terms' => array_filter( $excluded ), 'operator' => 'NOT IN',
        ] ],
    ];
    if ( 'latest' === $state['sort'] ) {
        unset( $args['meta_key'] );
        $args['orderby'] = [ 'date' => 'DESC', 'ID' => 'DESC' ];
    }
    if ( $state['category'] ) {
        $args['tax_query'][] = [ 'taxonomy' => 'product_cat', 'field' => 'slug', 'terms' => $state['category'] ];
    }
    if ( $state['size'] ) {
        $args['tax_query'][] = [ 'taxonomy' => 'pa_talla', 'field' => 'slug', 'terms' => $state['size'], 'operator' => 'IN' ];
    }
    if ( 'hide' === $state['soldout'] ) {
        $args['meta_query'] = [ [ 'key' => '_stock_status', 'value' => 'instock' ] ];
    }
    return $args;
}

function prs_catalog_product_ids( $input = [], $limit = 0 ) {
    static $cache = [];
    $state = prs_normalize_catalog_state( $input );
    $key = wp_json_encode( $state );
    if ( ! isset( $cache[ $key ] ) ) {
        $query = new WP_Query( prs_catalog_query_args( $state ) );
        $ids = array_map( 'intval', $query->posts );
        update_object_term_cache( $ids, 'product' );
        if ( ! $state['category'] && 'categories' === $state['sort'] ) {
            $priorities = array_flip( prs_get_product_category_order() );
            $ranked = [];
            foreach ( $ids as $position => $id ) {
                $rank = count( $priorities );
                $terms = get_the_terms( $id, 'product_cat' );
                foreach ( is_array( $terms ) ? $terms : [] as $term ) {
                    $slugs = [ $term->slug ];
                    foreach ( get_ancestors( $term->term_id, 'product_cat', 'taxonomy' ) as $ancestor_id ) {
                        $ancestor = get_term( $ancestor_id, 'product_cat' );
                        if ( $ancestor && ! is_wp_error( $ancestor ) ) $slugs[] = $ancestor->slug;
                    }
                    foreach ( $slugs as $slug ) $rank = min( $rank, $priorities[ $slug ] ?? count( $priorities ) );
                }
                $ranked[] = [ 'id' => $id, 'rank' => $rank, 'position' => $position ];
            }
            usort( $ranked, static function ( $a, $b ) {
                return ( $a['rank'] <=> $b['rank'] ) ?: ( $a['position'] <=> $b['position'] );
            } );
            $ids = array_column( $ranked, 'id' );
        }
        $cache[ $key ] = $ids;
    }
    return $limit > 0 ? array_slice( $cache[ $key ], 0, $limit ) : $cache[ $key ];
}

function prs_get_home_ordered_product_ids() {
    return prs_catalog_product_ids();
}

function prs_render_products_grid( $category_slug = '', $max_products = 0, $size_slug = '', $stock_filter = 'show', $sort = 'categories' ) {
    $ids = prs_catalog_product_ids( [ 'category' => $category_slug, 'size' => $size_slug, 'soldout' => $stock_filter, 'sort' => $sort ], (int) $max_products );
    update_meta_cache( 'post', $ids );
    return implode( '', array_map( 'prs_render_product_card', $ids ) );
}

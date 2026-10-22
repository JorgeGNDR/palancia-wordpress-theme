<?php
defined( 'ABSPATH' ) || exit;

/** Public, read-only endpoint; shares normalization and queries with the initial render. */
function prs_filter_products_by_category() {
    $input = wp_unslash( $_POST );
    $state = prs_normalize_catalog_state( [
        'category' => $input['category_slug'] ?? '',
        'size' => $input['size_slugs'] ?? '',
        'soldout' => $input['stock_filter'] ?? '',
        'sort' => $input['sort'] ?? '',
    ] );
    $html = prs_render_products_grid( $state['category'], 0, $state['size'], $state['soldout'], $state['sort'] );
    if ( ! $html ) $html = '<p class="prs-products-empty">' . esc_html__( 'No hay productos con estos filtros.', 'palancia-shop' ) . '</p>';
    wp_send_json_success( [ 'html' => $html, 'state' => $state ] );
}
add_action( 'wp_ajax_prs_filter_products', 'prs_filter_products_by_category' );
add_action( 'wp_ajax_nopriv_prs_filter_products', 'prs_filter_products_by_category' );

function prs_redirect_todo_category() {
    if ( is_tax( 'product_cat', 'todo' ) ) {
        wp_safe_redirect( home_url( '/' ), 301 );
        exit;
    }
}
add_action( 'template_redirect', 'prs_redirect_todo_category' );

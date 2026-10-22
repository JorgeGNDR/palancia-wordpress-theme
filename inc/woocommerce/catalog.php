<?php
defined( 'ABSPATH' ) || exit;

function prs_get_product_category_order() {
    return [ 'palancia-merch', 'chaquetas', 'chalecos', 'sudaderas', 'jerseis', 'tracksuits', 'pantalones', 'camisetas', 'bolsos', 'gafas', 'gorras'];
}

/**
 * Obtiene las categorías de producto filtradas y ordenadas según prioridad.
 * Centraliza la lógica usada en Home, Archivo y Single Product.
 */
function prs_get_sorted_product_categories() {
    // 1. Obtener todas las categorías no vacías
    $product_categories = get_terms( [
        'taxonomy'   => 'product_cat',
        'hide_empty' => true,
    ] );

    if ( is_wp_error( $product_categories ) || empty( $product_categories ) ) {
        return [];
    }

    // 2. Excluir categorías
    $excluded_slugs = [ 'tops', 'bottoms', 'accesorios' ];
    $product_categories = array_filter( $product_categories, function ( $cat ) use ( $excluded_slugs ) {
        return ! in_array( strtolower( $cat->slug ), $excluded_slugs, true );
    } );

    // 3. Ordenar según prioridad
    $priority_order = prs_get_product_category_order(); // Reutilizamos la función que ya tenías

    // Función auxiliar para ordenar objetos según un array de slugs
    $sorted = [];
    $lookup = [];

    // Indexar categorías por slug para búsqueda rápida
    foreach ( $product_categories as $cat ) {
        $lookup[ strtolower( $cat->slug ) ] = $cat;
    }

    // 1. PALANCIA MERCH primero
    if ( isset( $lookup['palancia-merch'] ) ) {
        $sorted[] = $lookup['palancia-merch'];
        unset( $lookup['palancia-merch'] );
    }

    // 2. Asegurar que 'todo' va después (usando la real si existe, o una fake si no)
    if ( isset( $lookup['todo'] ) ) {
        $sorted[] = $lookup['todo'];
        unset( $lookup['todo'] );
    } else {
        $sorted[] = (object) [
            'term_id' => 0,
            'name'    => 'Todo',
            'slug'    => 'todo',
        ];
    }

    // Añadir las prioritarias en orden
    foreach ( $priority_order as $slug ) {
        if ( isset( $lookup[ $slug ] ) ) {
            $sorted[] = $lookup[ $slug ];
            unset( $lookup[ $slug ] );
        }
    }

    // Añadir el resto (si hubiera alguna categoría nueva no listada en prioridad)
    foreach ( $lookup as $cat ) {
        $sorted[] = $cat;
    }

    return $sorted;
}

function prs_render_product_card( $post_id ) {
    $thumb_id   = get_post_thumbnail_id( $post_id );
    $img        = wp_get_attachment_image_src( $thumb_id, 'large' );
    $img_url    = $img ? $img[0] : wc_placeholder_img_src();
    $url        = get_permalink( $post_id );
    $title_attr = the_title_attribute( [ 'echo' => false, 'post' => $post_id ] );
    $product    = function_exists( 'wc_get_product' ) ? wc_get_product( $post_id ) : false;
    $price_html = $product ? $product->get_price_html() : '';
    $stock_text = $product && ! $product->is_in_stock() ? __( 'Agotado', 'palancia-shop' ) : '';

    $html  = '<a href="' . esc_url( $url ) . '" class="product-item">';
    $html .= '<img src="' . esc_url( $img_url ) . '" alt="' . esc_attr( $title_attr ) . '">';
    $html .= '<div class="product-overlay">';
    $html .= '<span class="product-title">' . esc_html( get_the_title( $post_id ) ) . '</span>';
    if ( $stock_text ) {
        $html .= '<span class="product-stock">' . esc_html( $stock_text ) . '</span>';
    }
    if ( $price_html ) {
        $html .= '<span class="product-price">' . wp_kses_post( $price_html ) . '</span>';
    }
    $html .= '</div>';
    $html .= '</a>';

    return $html;
}

function prs_get_available_size_terms() {
    if ( ! taxonomy_exists( 'pa_talla' ) ) {
        return [];
    }

    $terms = get_terms( [
        'taxonomy'   => 'pa_talla',
        'hide_empty' => false,
        'orderby'    => 'menu_order',
        'order'      => 'ASC',
    ] );

    if ( is_wp_error( $terms ) || empty( $terms ) ) {
        return [];
    }

    $alpha_sizes = [
        'XXXXS'    => 0,
        '4XSMALL'  => 0,
        'XXXS'     => 1,
        '3XSMALL'  => 1,
        'XXS'      => 2,
        '2XSMALL'  => 2,
        'XS'       => 3,
        'XSMALL'   => 3,
        'S'        => 4,
        'SMALL'    => 4,
        'M'        => 5,
        'MEDIUM'   => 5,
        'L'        => 6,
        'LARGE'    => 6,
        'XL'       => 7,
        'XLARGE'   => 7,
        'XXL'      => 8,
        '2XL'      => 8,
        'XXLARGE'  => 8,
        '2XLARGE'  => 8,
        'XXXL'     => 9,
        '3XL'      => 9,
        'XXXLARGE' => 9,
        '3XLARGE'  => 9,
        'XXXXL'    => 10,
        '4XL'      => 10,
        '4XLARGE'  => 10,
    ];

    usort(
        $terms,
        static function( $first, $second ) use ( $alpha_sizes ) {
            $get_sort_key = static function( $term ) use ( $alpha_sizes ) {
                $label = strtoupper( remove_accents( (string) $term->name ) );
                $key   = preg_replace( '/[^A-Z0-9]/', '', $label );

                if ( isset( $alpha_sizes[ $key ] ) ) {
                    return [ 0, $alpha_sizes[ $key ], $key ];
                }

                if ( preg_match( '/^W(\d{2,3})(?:L(\d{2,3}))?$/', $key, $matches ) ) {
                    return [ 1, (int) $matches[1], isset( $matches[2] ) ? (int) $matches[2] : 0 ];
                }

                if ( preg_match( '/^(\d+(?:\.\d+)?)$/', $key, $matches ) ) {
                    return [ 2, (float) $matches[1], $key ];
                }

                return [ 3, 0, $key ];
            };

            $first_key  = $get_sort_key( $first );
            $second_key = $get_sort_key( $second );
            $comparison = $first_key <=> $second_key;

            return 0 !== $comparison ? $comparison : (int) $first->term_id <=> (int) $second->term_id;
        }
    );

    return $terms;
}

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

/** Render WooCommerce's own loop action without leaking its global product context. */
function prs_product_card_cart_action( $card_product ) {
    $had_product = array_key_exists( 'product', $GLOBALS );
    $previous_product = $GLOBALS['product'] ?? null;
    $GLOBALS['product'] = $card_product;
    // Cards also render inside admin-ajax requests: use a frontend URL as the
    // native non-JavaScript fallback, then let extension filters customize it.
    $action_url = static function ( $url, $action_product ) use ( $card_product ) {
        if ( $action_product->get_id() === $card_product->get_id() && $action_product->is_type('simple') && $action_product->is_purchasable() && $action_product->is_in_stock() ) {
            return add_query_arg( 'add-to-cart', $action_product->get_id(), $action_product->get_permalink() );
        }
        return $url;
    };
    add_filter( 'woocommerce_product_add_to_cart_url', $action_url, 0, 2 );
    ob_start();
    try {
        woocommerce_template_loop_add_to_cart();
        $html = ob_get_contents();
    } finally {
        ob_end_clean();
        remove_filter( 'woocommerce_product_add_to_cart_url', $action_url, 0 );
        if ( $had_product ) $GLOBALS['product'] = $previous_product;
        else unset( $GLOBALS['product'] );
    }
    return $html;
}

function prs_render_product_card( $post_id ) {
    $product = wc_get_product( $post_id );
    if ( ! $product ) return '';
    $img = wp_get_attachment_image_src( $product->get_image_id(), 'large' );
    $img_url = $img ? $img[0] : wc_placeholder_img_src();
    $url = $product->get_permalink();
    $title = $product->get_name();
    $size_terms = wc_get_product_terms( $post_id, 'pa_talla', [ 'fields' => 'names' ] );
    $size = ! is_wp_error( $size_terms ) && $size_terms ? implode( ', ', $size_terms ) : $product->get_attribute( 'talla' );
    $price_html = $product->get_price_html();

    // The photo, details link and cart action are siblings, never nested links.
    $html = '<article class="product-item" data-product-id="' . esc_attr( $post_id ) . '">';
    $html .= '<a class="product-image-link" href="' . esc_url( $url ) . '"><img src="' . esc_url( $img_url ) . '" alt="' . esc_attr( $title ) . '"></a>';
    $html .= '<div class="product-overlay">';
    $html .= '<a class="product-title" href="' . esc_url( $url ) . '" title="' . esc_attr( $title ) . '">' . esc_html( $title ) . '</a>';
    $html .= '<div class="product-card-meta">';
    if ( $price_html ) $html .= '<span class="product-price">' . wp_kses_post( $price_html ) . '</span>';
    if ( $size ) $html .= '<span class="product-size">' . esc_html__( 'Talla:', 'palancia-shop' ) . ' ' . esc_html( $size ) . '</span>';
    if ( ! $product->is_in_stock() ) $html .= '<span class="product-stock">' . esc_html__( 'Agotado', 'palancia-shop' ) . '</span>';
    $html .= '</div>';
    $html .= '<div class="product-card-action">' . prs_product_card_cart_action( $product ) . '</div>';
    $html .= '</div></article>';
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

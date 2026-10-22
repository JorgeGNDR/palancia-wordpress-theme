<?php
defined( 'ABSPATH' ) || exit;

function prs_cleanup_single_summary_hooks() {
    // Quitar rating, meta, compartir
    remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10 );
    remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
    remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_sharing', 50 );

    // Quitar descripción corta por defecto (excerpt)
    remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 );

    // Quitar tabs, upsells y relacionados de abajo
    remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );
    remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 );
    remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );

    // Quitar breadcrumbs (Inicio / Categoría / Producto)
    remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );

    // Quitar sidebar de WooCommerce (Buscar, Páginas, Archivos, Categorías)
    remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );
}
add_action( 'init', 'prs_cleanup_single_summary_hooks' );

// Mostrar talla debajo del título (atributo pa_talla)
function prs_show_product_size() {
    global $product;

    if ( ! $product ) {
        return;
    }

    $size_terms = wc_get_product_terms( $product->get_id(), 'pa_talla', [ 'fields' => 'names' ] );

    if ( ! empty( $size_terms ) ) {
        $tallas = implode( ', ', $size_terms );
        echo '<div class="prs-product-size"><span>Talla:</span> ' . esc_html( $tallas ) . '</div>';
    }
}
add_action( 'woocommerce_single_product_summary', 'prs_show_product_size', 6 );

// Descripción larga en el resumen, como texto simple
function prs_product_long_description() {
    global $post;

    if ( ! $post ) {
        return;
    }

    $content = apply_filters( 'the_content', $post->post_content );

    if ( ! $content ) {
        return;
    }

    echo '<div class="prs-product-long-description">' . $content . '</div>';
}
// La ponemos debajo del precio y el botón de añadir al carrito.
add_action( 'woocommerce_single_product_summary', 'prs_product_long_description', 35 );


// ------------ GALERÍA PERSONALIZADA ------------ //

// Sustituir galería nativa por la nuestra
function prs_override_wc_gallery() {
    // Elimina la galería por defecto de WooCommerce
    remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20 );
    // Añade nuestra galería en el mismo hook
    add_action( 'woocommerce_before_single_product_summary', 'prs_custom_product_gallery', 20 );
}
add_action( 'init', 'prs_override_wc_gallery' );

// Galería centrada, sin miniaturas, con flechas y loop
function prs_custom_product_gallery() {
    if ( ! is_product() ) return;

    global $product;
    if ( ! $product ) return;

    $prev_url = '';
    $next_url = '';
    $ordered_ids = prs_get_home_ordered_product_ids();
    $current_id  = $product->get_id();
    $total_ids   = count( $ordered_ids );

    if ( $total_ids > 1 ) {
        $current_index = array_search( $current_id, $ordered_ids, true );
        if ( $current_index !== false ) {
            $prev_index = ( $current_index - 1 + $total_ids ) % $total_ids;
            $next_index = ( $current_index + 1 ) % $total_ids;
            $prev_url   = get_permalink( $ordered_ids[ $prev_index ] );
            $next_url   = get_permalink( $ordered_ids[ $next_index ] );
        }
    }

    $image_ids = [];
    $featured = $product->get_image_id();
    if ( $featured ) $image_ids[] = $featured;

    $gallery = $product->get_gallery_image_ids();
    if ( $gallery ) $image_ids = array_merge($image_ids, $gallery);

    if ( empty( $image_ids ) ) {
        $placeholder_url = wc_placeholder_img_src( 'large' );
        echo '<div class="prs-product-gallery" data-total="1" data-prev-url="' . esc_url( $prev_url ) . '" data-next-url="' . esc_url( $next_url ) . '">';
        echo '<div class="prs-product-slide is-active" data-index="0">';
        echo '<img src="' . esc_url( $placeholder_url ) . '" alt="' . esc_attr( $product->get_name() ) . '">';
        echo '</div></div>';
        return;
    }

    echo '<div class="prs-product-gallery" data-total="' . count( $image_ids ) . '" data-prev-url="' . esc_url( $prev_url ) . '" data-next-url="' . esc_url( $next_url ) . '">';

    foreach ($image_ids as $i => $id) {
        echo '<div class="prs-product-slide '.($i === 0 ? 'is-active' : '').'" data-index="'.$i.'">';
        echo wp_get_attachment_image($id, 'large');
        echo '</div>';
    }

    if (count($image_ids) > 1) {
        echo '<button class="prs-product-nav prs-prev" type="button">&lsaquo;</button>';
        echo '<button class="prs-product-nav prs-next" type="button">&rsaquo;</button>';
    }

    echo '</div>';

    if ( count( $image_ids ) > 1 ) {
        echo '<div class="prs-product-dots" aria-label="Galería de producto">';
        foreach ( $image_ids as $i => $id ) {
            $active = $i === 0 ? ' is-active' : '';
            echo '<button class="prs-product-dot' . $active . '" type="button" aria-label="Ver imagen ' . ( $i + 1 ) . '" data-index="' . $i . '"></button>';
        }
        echo '</div>';
    }
}

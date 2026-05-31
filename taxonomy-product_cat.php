<?php
/**
 * Plantilla para categorias de producto (/collections/{slug})
 * Mismo layout que la home: filtros a la izquierda + grid en el centro.
 *
 * @package WooCommerce/Templates
 * @version 4.7.0
 */

get_header();

// Comprobamos WooCommerce.
if ( ! class_exists( 'WooCommerce' ) ) {
    echo '<p>Instala y activa WooCommerce para mostrar productos.</p>';
    get_footer();
    exit;
}

// Categoria actual.
$current_term = get_queried_object();

// Si la categoria es TODO, redirigimos a la home.
if ( isset( $current_term->slug ) && $current_term->slug === 'todo' ) {
    wp_safe_redirect( home_url( '/' ), 301 );
    exit;
}

// Usar la función centralizada
$product_categories     = prs_get_sorted_product_categories();
$current_category_slug  = isset( $current_term->slug ) ? $current_term->slug : '';

include locate_template( 'template-parts/product-grid.php' );

get_footer();

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

// Obtener categorias para los filtros (igual que en la home).
$product_categories = get_terms( [
    'taxonomy'   => 'product_cat',
    'hide_empty' => true,
] );

// Excluir categorias que ya no deben mostrarse en el menu.
$excluded_slugs     = [ 'tops', 'bottoms', 'accesorios' ];
$product_categories = array_values( array_filter( $product_categories, function ( $cat ) use ( $excluded_slugs ) {
    return ! in_array( strtolower( $cat->slug ), $excluded_slugs, true );
} ) );

// Orden personalizado solicitado.
$priority_order   = [ 'todo', 'chaquetas', 'chalecos', 'sudaderas', 'jerseis', 'tracksuits', 'pantalones', 'camisetas', 'bolsos', 'gafas', 'gorras' ]; // slugs en minusculas
$final_categories = [];

// 1. Primero las prioritarias.
foreach ( $priority_order as $slug ) {
    foreach ( $product_categories as $cat ) {
        if ( strtolower( $cat->slug ) === $slug ) {
            $final_categories[] = $cat;
        }
    }
}

// 2. Luego el resto sin duplicar.
foreach ( $product_categories as $cat ) {
    if ( ! in_array( $cat, $final_categories, true ) ) {
        $final_categories[] = $cat;
    }
}

$product_categories     = $final_categories;
$current_category_slug  = isset( $current_term->slug ) ? $current_term->slug : '';

include locate_template( 'template-parts/product-grid.php' );

get_footer();

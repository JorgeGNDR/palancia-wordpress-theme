<?php
/**
 * Plantilla Home: galeria de productos con filtros
 */

get_header();

if ( ! class_exists('WooCommerce') ) {
    echo '<p>Instala y activa WooCommerce para mostrar productos.</p>';
    get_footer();
    exit;
}

// Usar la función centralizada
$product_categories     = prs_get_sorted_product_categories();
$current_category_slug  = '';

include locate_template( 'template-parts/product-grid.php' );

get_footer();

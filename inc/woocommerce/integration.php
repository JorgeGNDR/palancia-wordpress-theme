<?php
defined( 'ABSPATH' ) || exit;

function prs_single_product_template_override( $template ) {
	if ( is_singular( 'product' ) ) {
		$custom = get_theme_file_path( 'custom-single-product.php' );
		if ( file_exists( $custom ) ) {
			return $custom;
		}
	}
	return $template;
}
add_filter( 'template_include', 'prs_single_product_template_override', 11 );

// --------- Reglas: productos 1/1 y sin avisos estándar ---------
// Forzar que todos los productos sean vendidos de forma individual (sin cantidades)
add_filter( 'woocommerce_is_sold_individually', '__return_true', 10, 2 );

// Suprimir mensaje de "añadido al carrito"
add_filter( 'wc_add_to_cart_message_html', '__return_empty_string' );

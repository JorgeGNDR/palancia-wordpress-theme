<?php
/**
 * Plantilla de carrito custom del tema, sin usar la tabla estándar.
 */

defined( 'ABSPATH' ) || exit;

get_header();

// Notificaciones de WooCommerce
woocommerce_output_all_notices();

// Renderizado custom del carrito (misma salida que antes)
if ( function_exists( 'prs_render_custom_cart' ) ) {
    prs_render_custom_cart();
} else {
    // Fallback al carrito estándar si faltara la función
    echo do_shortcode( '[woocommerce_cart]' );
}

get_footer();

<?php
/** Theme bootstrap. WordPress loads plugins before the theme. */
defined( 'ABSPATH' ) || exit;

foreach ( [ 'version', 'setup', 'assets', 'customizer', 'contact' ] as $module ) {
    require_once get_parent_theme_file_path( 'inc/' . $module . '.php' );
}

function prs_load_woocommerce_modules() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        return;
    }
    foreach ( [ 'catalog-state', 'catalog-query', 'catalog', 'catalog-endpoint', 'product', 'cart', 'checkout', 'integration' ] as $module ) {
        require_once get_parent_theme_file_path( 'inc/woocommerce/' . $module . '.php' );
    }
}
add_action( 'after_setup_theme', 'prs_load_woocommerce_modules', 0 );

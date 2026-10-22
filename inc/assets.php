<?php
defined( 'ABSPATH' ) || exit;

function prs_enqueue_assets() {
    // CSS principal
    wp_enqueue_style(
        'palancia-retro-shop-style',
        get_parent_theme_file_uri( 'style.css' ),
        [],
        prs_get_theme_version() . '.' . filemtime( get_parent_theme_file_path( 'style.css' ) )
    );

    $previous = 'palancia-retro-shop-style';
    foreach ( [ 'layout', 'catalog', 'panels', 'product', 'zoom', 'cart', 'forms', 'pages', 'responsive' ] as $component ) {
        $handle = 'prs-' . $component . '-style';
        $path = 'assets/css/' . $component . '.css';
        wp_enqueue_style( $handle, get_theme_file_uri( $path ), [ $previous ], prs_get_asset_version( $path ) );
        $previous = $handle;
    }
    if ( is_child_theme() ) {
        wp_enqueue_style( 'prs-child-style', get_stylesheet_uri(), [ $previous ], prs_get_asset_version( 'style.css' ) );
    }

    // Material Symbols (icons)
    wp_enqueue_style(
        'palancia-material-symbols',
        'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=menu,shopping_bag,add_shopping_cart,add&display=swap',
        [],
        null
    );

    if ( class_exists( 'WooCommerce' ) && ( is_front_page() || is_tax( 'product_cat' ) || is_product() ) ) {
        wp_enqueue_script( 'prs-panels', get_theme_file_uri( 'assets/js/panels.js' ), [], prs_get_asset_version( 'assets/js/panels.js' ), true );
    }

    // JS del slider solo en ficha de producto
    if ( function_exists( 'is_product' ) && is_product() ) {
        wp_enqueue_script(
            'prs-product-gallery',
            get_theme_file_uri( 'assets/js/product-gallery.js' ),
            [ 'prs-panels' ],
            prs_get_asset_version( 'assets/js/product-gallery.js' ),
            true
        );

        wp_enqueue_script(
            'prs-add-to-cart-feedback',
            get_theme_file_uri( 'assets/js/add-to-cart-feedback.js' ),
            [],
            prs_get_asset_version( 'assets/js/add-to-cart-feedback.js' ),
            true
        );
    }
}
add_action( 'wp_enqueue_scripts', 'prs_enqueue_assets' );

function prs_enqueue_checkout_styles() {
    if ( function_exists( 'is_checkout' ) && is_checkout() ) {
        wp_enqueue_style(
            'prs-checkout',
            get_theme_file_uri( 'assets/css/checkout.css' ),
            [ 'prs-responsive-style' ],
            prs_get_asset_version( 'assets/css/checkout.css' )
        );
    }
}
add_action( 'wp_enqueue_scripts', 'prs_enqueue_checkout_styles', 30 );

// Encolar el script de filtrado de productos con AJAX
function prs_enqueue_filter_script() {
    if ( ! class_exists( 'WooCommerce' ) || ( ! is_front_page() && ! is_tax( 'product_cat' ) ) ) {
        return;
    }

    wp_enqueue_script( 'prs-shop-panels', get_theme_file_uri( 'assets/js/shop-panels.js' ), [ 'prs-panels' ], prs_get_asset_version( 'assets/js/shop-panels.js' ), true );
    wp_enqueue_script( 'prs-grid-motion', get_theme_file_uri( 'assets/js/grid-motion.js' ), [], prs_get_asset_version( 'assets/js/grid-motion.js' ), true );
    wp_enqueue_script(
        'prs-filter-products',
        get_theme_file_uri( 'assets/js/filter-products.js' ),
        [ 'prs-shop-panels', 'prs-grid-motion' ],
        prs_get_asset_version( 'assets/js/filter-products.js' ),
        true
    );

    wp_localize_script('prs-filter-products', 'prsFilterProducts', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
    ]);
}
add_action('wp_enqueue_scripts', 'prs_enqueue_filter_script');

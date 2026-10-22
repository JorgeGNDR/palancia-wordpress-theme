<?php
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) exit;
if ( ! class_exists( 'WooCommerce' ) ) {
    prs_enqueue_assets();
    if ( function_exists( 'prs_render_products_grid' ) ) WP_CLI::error('Woo modules loaded without WooCommerce');
    WP_CLI::success('Theme loads and enqueues without WooCommerce');
    return;
}
global $wp_query;
$wp_query = new WP_Query(); $wp_query->is_home = true;
prs_enqueue_assets(); prs_enqueue_filter_script();
require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
$manager = new WP_Customize_Manager(); prs_customize_register($manager);
if ( ! $manager->get_setting('prs_logo_height_mobile') ) WP_CLI::error('Customizer missing');
if ( wp_scripts()->registered['prs-filter-products']->deps !== ['prs-shop-panels'] ) WP_CLI::error('Catalog dependency missing');
if ( wp_scripts()->registered['prs-shop-panels']->deps !== ['prs-panels'] ) WP_CLI::error('Panel dependency missing');
if ( ! isset(wp_styles()->registered['prs-responsive-style']) ) WP_CLI::error('Component stylesheet missing');
WP_CLI::success('Theme bootstrap, assets and Customizer work with WooCommerce');

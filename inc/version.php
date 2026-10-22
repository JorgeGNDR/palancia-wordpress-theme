<?php
defined( 'ABSPATH' ) || exit;

function prs_get_theme_version() {
    static $version = null;

    if ( null === $version ) {
        $version = wp_get_theme()->get( 'Version' );
        $version = $version ?: '0.0.0';
    }

    return $version;
}

function prs_get_asset_version( $relative_path ) {
    $absolute_path = get_theme_file_path( ltrim( $relative_path, '/' ) );
    $modified      = file_exists( $absolute_path ) ? filemtime( $absolute_path ) : 0;

    return prs_get_theme_version() . ( $modified ? '.' . $modified : '' );
}

function prs_output_theme_version_marker() {
    $version = prs_get_theme_version();

    echo '<meta name="palancia-theme-version" content="' . esc_attr( $version ) . '">' . "\n";
    echo '<!-- Palancia Web Shop v' . esc_html( $version ) . ' -->' . "\n";
}
add_action( 'wp_head', 'prs_output_theme_version_marker', 1 );

function prs_add_theme_version_to_admin_bar( $admin_bar ) {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $admin_bar->add_node( [
        'id'    => 'prs-theme-version',
        'title' => 'PALANCIA v' . prs_get_theme_version(),
        'href'  => admin_url( 'themes.php' ),
        'meta'  => [
            'title' => __( 'Version activa del tema Palancia', 'palancia-shop' ),
        ],
    ] );
}
add_action( 'admin_bar_menu', 'prs_add_theme_version_to_admin_bar', 100 );

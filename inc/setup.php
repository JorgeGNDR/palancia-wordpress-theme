<?php
defined( 'ABSPATH' ) || exit;

function prs_setup_theme() {
    load_theme_textdomain( 'palancia-shop', get_parent_theme_file_path( 'languages' ) );
    add_theme_support( 'woocommerce' );
    add_theme_support( 'title-tag' );
    add_theme_support( 'custom-logo', [
        'height'      => 400,
        'width'       => 400,
        'flex-height' => true,
        'flex-width'  => true,
    ] );
}
add_action( 'after_setup_theme', 'prs_setup_theme' );

function prs_sanitize_css_size( $value ) {
    $value = trim( (string) $value );

    if ( preg_match( '/^\d+(\.\d+)?(px|rem|em|vw|vh|%)$/', $value ) ) {
        return $value;
    }

    if ( preg_match( '/^(clamp|min|max|calc)\([0-9a-zA-Z\s.,+\-*\/()%]+\)$/', $value ) ) {
        return $value;
    }

    return '';
}

function prs_sanitize_positive_int( $value ) {
    return max( 1, absint( $value ) );
}

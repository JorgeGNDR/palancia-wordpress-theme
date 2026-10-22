<?php
defined( 'ABSPATH' ) || exit;

function prs_catalog_scalar( $value ) {
    return is_scalar( $value ) ? (string) $value : '';
}

function prs_sanitize_product_sort( $value ) {
    return is_string( $value ) && in_array( $value, [ 'categories', 'latest', 'price-asc', 'price-desc' ], true ) ? $value : 'categories';
}

function prs_normalize_catalog_state( $input = [] ) {
    $category = sanitize_title( prs_catalog_scalar( $input['category'] ?? '' ) );
    $sizes = $input['size'] ?? '';
    $sizes = is_array( $sizes ) ? $sizes : explode( ',', prs_catalog_scalar( $sizes ) );
    $sizes = array_values( array_unique( array_filter( array_map( static function ( $size ) {
        return sanitize_title( prs_catalog_scalar( $size ) );
    }, $sizes ) ) ) );
    return [
        'category' => 'todo' === $category ? '' : $category,
        'size'     => $sizes,
        'soldout'  => 'hide' === ( $input['soldout'] ?? '' ) ? 'hide' : 'show',
        'sort'     => prs_sanitize_product_sort( $input['sort'] ?? '' ),
    ];
}

function prs_catalog_page_state( $category = '' ) {
    $input = wp_unslash( $_GET );
    $input['category'] = $category;
    return prs_normalize_catalog_state( $input );
}

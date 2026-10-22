<?php
/** Run only in a disposable WordPress installation: wp eval-file tests/wordpress.php */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) exit;
if ( wp_parse_url( home_url(), PHP_URL_HOST ) !== 'palancia.test' ) {
    WP_CLI::error( 'This test creates products. Use the disposable palancia.test installation.' );
}
function prs_test_expect( $condition, $message ) {
    if ( ! $condition ) WP_CLI::error( $message );
    WP_CLI::log( 'OK: ' . $message );
}
update_option( 'woocommerce_hide_out_of_stock_items', 'no' );
register_taxonomy( 'pa_talla', 'product', [ 'public' => false ] );
function prs_test_term( $name, $slug, $parent = 0 ) {
    return term_exists( $slug, 'product_cat' ) ?: wp_insert_term( $name, 'product_cat', [ 'slug' => $slug, 'parent' => $parent ] );
}
$jacket = prs_test_term( 'Chaquetas', 'chaquetas' );
$vest = prs_test_term( 'Chalecos', 'chalecos' );
$child = prs_test_term( 'Chaquetas cortas', 'cortas', $jacket['term_id'] );
$other = prs_test_term( 'Otras', 'otras' );
$ids = [];
$fixtures = [
    'a' => [20, '2020-01-01', [$jacket['term_id']], 'visible', 'instock', 'xl'],
    'b' => [10, '2021-01-01', [$jacket['term_id']], 'visible', 'outofstock', 'm'],
    'c' => [30, '2022-01-01', [$jacket['term_id'], $vest['term_id']], 'catalog', 'instock', 'xl'],
    'd' => [40, '2023-01-01', [$child['term_id']], 'visible', 'instock', 'm'],
    'e' => [50, '2024-01-01', [$other['term_id']], 'visible', 'instock', 's'],
    'hidden' => [100, '2025-01-01', [$jacket['term_id']], 'hidden', 'instock', 'xl'],
    'search' => [200, '2025-02-01', [$jacket['term_id']], 'search', 'instock', 'xl'],
];
foreach ( $fixtures as $key => [$price, $date, $categories, $visibility, $stock, $size] ) {
    $existing = wc_get_product_id_by_sku( 'palancia-test-' . $key );
    $product = $existing ? wc_get_product( $existing ) : new WC_Product_Simple();
    $product->set_sku( 'palancia-test-' . $key );
    $product->set_name( 'Fixture ' . $key ); $product->set_status( 'publish' );
    $product->set_regular_price( $price ); $product->set_date_created( $date );
    $product->set_category_ids( $categories ); $product->set_catalog_visibility( $visibility );
    $product->set_stock_status( $stock ); $ids[$key] = $product->save();
    wp_set_object_terms( $ids[$key], $size, 'pa_talla' );
}
$expected = static function ( $keys ) use ( $ids ) { return array_map( static fn( $key ) => $ids[$key], $keys ); };
prs_test_expect( prs_catalog_product_ids() === $expected(['d','c','a','b','e']), 'Category priority, child categories, no duplicates and hidden/search-only exclusions' );
prs_test_expect( prs_catalog_product_ids(['sort'=>'price-asc']) === $expected(['b','a','c','d','e']), 'Ascending prices' );
prs_test_expect( prs_catalog_product_ids(['sort'=>'price-desc']) === $expected(['e','d','c','a','b']), 'Descending prices' );
prs_test_expect( prs_catalog_product_ids(['sort'=>'latest']) === $expected(['e','d','c','b','a']), 'Latest published first' );
prs_test_expect( prs_catalog_product_ids(['size'=>'xl', 'soldout'=>'hide']) === $expected(['c','a']), 'Size and stock filters compose' );
prs_test_expect( prs_catalog_product_ids(['category'=>'chaquetas', 'soldout'=>'hide']) === $expected(['d','c','a']), 'Parent category includes children and stock restriction' );
prs_test_expect( prs_get_home_ordered_product_ids() === prs_catalog_product_ids(), 'Navigation and grid share ordering' );
prs_test_expect( prs_catalog_product_ids([],2) === $expected(['d','c']), 'Limit applies after grouping' );
prs_test_expect( prs_normalize_catalog_state(['category'=>[], 'size'=>['xl', ['bad']], 'sort'=>['bad']]) === ['category'=>'','size'=>['xl'],'soldout'=>'show','sort'=>'categories'], 'Malformed input is safe and normalized' );
global $wpdb;
$before = $wpdb->num_queries; prs_catalog_product_ids();
prs_test_expect( $wpdb->num_queries === $before, 'Repeated catalog lookup is cached only within the request' );
$_GET = ['size'=>'xl', 'sort'=>'price-asc']; $product_categories = prs_get_sorted_product_categories(); $current_category_slug='';
ob_start(); include get_theme_file_path('template-parts/product-grid.php'); $html=ob_get_clean();
prs_test_expect( substr_count($html, 'id="product-sort-modal"')===1 && substr_count($html, 'class="product-sort-trigger"')===2, 'Real PHP templates render one dialog and two triggers' );
prs_test_expect( str_contains($html, 'data-catalog=') && !str_contains($html, 'Fixture hidden'), 'Rendered catalog contains canonical state and excludes hidden products' );
file_put_contents('/tmp/palancia-wp-integration/rendered-grid.html', $html);
WP_CLI::success('WordPress/WooCommerce integration checks passed.');

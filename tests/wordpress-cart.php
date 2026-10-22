<?php
/** wp eval-file tests/wordpress-cart.php -- only in the disposable local test site. */
if ( ! defined('WP_CLI') || ! WP_CLI ) exit;
if ( wp_parse_url(home_url(), PHP_URL_HOST) !== 'palancia.test' ) WP_CLI::error('Use the disposable palancia.test site');
wc_load_cart();
WC()->cart->empty_cart();
foreach ( ['a','c','d'] as $key ) {
    $id = wc_get_product_id_by_sku('palancia-test-' . $key);
    if (!$id || !WC()->cart->add_to_cart($id)) WP_CLI::error('Test products missing; run tests/wordpress.php first');
}
$names = ['Chaqueta Alpha Industries beige', 'Chaqueta Avirex negra blanca', 'Chaqueta vaquera'];
$index = 0;
foreach (WC()->cart->get_cart() as $item) $item['data']->set_name($names[$index++]);
$terms = wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_title'=>'Test terms']);
update_option('woocommerce_terms_page_id', $terms);
ob_start(); prs_render_custom_cart(); $cart = ob_get_clean();
$doc = new DOMDocument(); @$doc->loadHTML('<?xml encoding="UTF-8">' . $cart);
$xpath = new DOMXPath($doc);
if ($xpath->query('//*[contains(@class,"pal-cart-item")]')->length!==3) WP_CLI::error('Wrong cart row count');
if ($xpath->query('//*[contains(@class,"pal-cart-main")]//a[contains(@class,"pal-cart-remove")]')->length!==3) WP_CLI::error('Remove action must be with product information');
if (substr_count($cart,'class="pal-cart-price"')!==3) WP_CLI::error('Prices must be rendered once per row');
if (strpos($cart, 'id="pal-cart-terms"')===false) WP_CLI::error('Terms control missing');
if (strpos($cart, 'Máximo 1 unidad')!==false) WP_CLI::error('Removed notice returned');
$_SERVER['SERVER_NAME'] = 'palancia.test';
ob_start(); get_header(); echo $cart; get_footer(); $html=ob_get_clean();
$html=str_replace(['http://palancia.test/wordpress', 'http://palancia.test'],'http://127.0.0.1:8765',$html);
file_put_contents(ABSPATH.'cart-preview.html',$html);
WP_CLI::success('Cart markup, actions, terms and prices passed; cart-preview.html generated.');

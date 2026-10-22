<?php
/** Run only in the disposable site: wp eval-file tests/wordpress-cards.php */
if (!defined('WP_CLI') || !WP_CLI) exit;
if (wp_parse_url(home_url(), PHP_URL_HOST) !== 'palancia.test') WP_CLI::error('Use disposable palancia.test only');
if (!taxonomy_exists('pa_talla')) {
    wc_create_attribute(['name'=>'Talla', 'slug'=>'talla', 'type'=>'select']);
    register_taxonomy('pa_talla', 'product');
}
$a=wc_get_product_id_by_sku('palancia-test-a');
$b=wc_get_product_id_by_sku('palancia-test-b');
if (!$a || !$b) WP_CLI::error('Run tests/wordpress.php first');
$sentinel=(object)['context'=>'preserved'];
$GLOBALS['product']=$sentinel;
$_SERVER['REQUEST_URI']='/wp-admin/admin-ajax.php';
$native_url=wc_get_product($a)->add_to_cart_url();
$html=prs_render_product_card($a);
if ($GLOBALS['product'] !== $sentinel) WP_CLI::error('Card leaked its product context');
if (wc_get_product($a)->add_to_cart_url() !== $native_url) WP_CLI::error('Card leaked its action URL filter');
$doc=new DOMDocument(); @$doc->loadHTML('<?xml encoding="UTF-8">'.$html);
$xpath=new DOMXPath($doc);
if ($xpath->query('//article[@data-product-id="'.$a.'"]')->length !== 1) WP_CLI::error('Card identity missing');
if ($xpath->query('//a//a')->length) WP_CLI::error('Nested interactive links');
if ($xpath->query('//article/a[contains(@class,"product-image-link")]')->length !== 1) WP_CLI::error('Photo must link to product');
if ($xpath->query('//a[contains(@class,"ajax_add_to_cart") and @data-product_id="'.$a.'"]')->length !== 1) WP_CLI::error('Native AJAX action missing');
$action=$xpath->query('//a[contains(@class,"ajax_add_to_cart")]')->item(0)->getAttribute('href');
if ($action !== add_query_arg('add-to-cart',$a,get_permalink($a))) WP_CLI::error('Cart fallback must use a frontend URL, including in AJAX responses');
$size_text=$xpath->query('//span[contains(@class,"product-size")]')->item(0)?->textContent ?? '';
if (!str_contains(strtoupper($size_text),'XL')) WP_CLI::error('Size must come from product attribute');
if (str_contains(prs_render_product_card($b),'ajax_add_to_cart')) WP_CLI::error('Sold-out product cannot add directly');
$existing=wc_get_product_id_by_sku('palancia-test-variable');
$variable=$existing ? wc_get_product($existing) : new WC_Product_Variable();
$variable->set_name('Variable test'); $variable->set_sku('palancia-test-variable');
$variable->set_catalog_visibility('hidden'); $variable->set_status('publish'); $variable->save();
$variable_html=prs_render_product_card($variable->get_id());
if (str_contains($variable_html,'ajax_add_to_cart')) WP_CLI::error('Variable product requires option selection');
if (!str_contains($variable_html,esc_url($variable->get_permalink()))) WP_CLI::error('Variable product must link to options');
wc_load_cart(); WC()->cart->empty_cart(); WC()->cart->add_to_cart($a);
$fragments=apply_filters('woocommerce_add_to_cart_fragments',[]);
if (($fragments['.cart-count-inline'] ?? '') !== '<span class="cart-count-inline">1</span>') WP_CLI::error('Header count fragment is wrong');
unset($GLOBALS['product']);
WP_CLI::success('Cards: valid links, size, native actions, sold out, variations, global context and header count passed.');

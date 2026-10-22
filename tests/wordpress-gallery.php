<?php
/** Run only in the disposable local site: wp eval-file tests/wordpress-gallery.php */
if ( ! defined('WP_CLI') || ! WP_CLI ) exit;
if ( wp_parse_url(home_url(), PHP_URL_HOST) !== 'palancia.test' ) WP_CLI::error('Use the disposable palancia.test site');
$id = wc_get_product_id_by_sku('palancia-test-a');
if (!$id) WP_CLI::error('Run tests/wordpress.php first');
$attachments = [];
require_once ABSPATH . 'wp-admin/includes/image.php';
for ($i = 0; $i < 2; $i++) {
    $ids = get_posts(['post_type'=>'attachment', 'name'=>'palancia-gallery-test-' . $i, 'fields'=>'ids']);
    if ($ids) { $attachments[] = $ids[0]; continue; }
    $file = wp_upload_bits('palancia-gallery-test-' . $i . '.webp', null, file_get_contents(WP_PLUGIN_DIR . '/woocommerce/assets/images/placeholder.webp'));
    if (!empty($file['error'])) WP_CLI::error($file['error']);
    $attachment = wp_insert_attachment(['post_title'=>'Imagen de prueba ' . ($i + 1), 'post_name'=>'palancia-gallery-test-' . $i, 'post_mime_type'=>'image/webp', 'post_status'=>'inherit'], $file['file']);
    wp_update_attachment_metadata($attachment, wp_generate_attachment_metadata($attachment, $file['file']));
    $attachments[] = $attachment;
}
$product = wc_get_product($id);
$product->set_image_id($attachments[0]);
$product->set_gallery_image_ids([$attachments[1]]);
$product->save();
$GLOBALS['wp_query'] = new WP_Query(['post_type'=>'product', 'p'=>$id]);
$GLOBALS['product'] = $product;
$_SERVER['SERVER_NAME'] = 'palancia.test';
ob_start(); prs_custom_product_gallery(); $gallery = ob_get_clean();
if (substr_count($gallery, 'data-full-src=') !== 2) WP_CLI::error('Full-resolution sources missing');
foreach ($attachments as $attachment) {
    if (strpos($gallery, esc_url(wp_get_attachment_image_url($attachment, 'full'))) === false) WP_CLI::error('Wrong original image URL');
}
ob_start();
get_header();
echo '<div class="prs-shop-layout prs-product-page"><div class="prs-product-gallery-wrapper">' . $gallery . '</div></div>';
get_footer();
$html = ob_get_clean();
if (strpos($html, 'product-gallery.js') === false) WP_CLI::error('Gallery script was not enqueued on product page');
$html = str_replace(['http://palancia.test/wordpress', 'http://palancia.test'], 'http://127.0.0.1:8765', $html);
file_put_contents(ABSPATH . 'gallery-preview.html', $html);
WP_CLI::success('WordPress gallery outputs original image URLs and enqueues the viewer; gallery-preview.html generated.');

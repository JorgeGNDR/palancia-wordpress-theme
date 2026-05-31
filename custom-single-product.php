<?php
defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

while ( have_posts() ) :
	the_post();

	global $product;
	if ( ! $product ) {
		$product = wc_get_product( get_the_ID() );
	}

	do_action( 'woocommerce_before_single_product' );

	if ( post_password_required() ) {
		echo get_the_password_form();
		do_action( 'woocommerce_after_single_product' );
		get_footer( 'shop' );
		return;
	}
	?>

	<?php do_action( 'woocommerce_before_main_content' ); ?>

	<?php
	$product_categories    = prs_get_sorted_product_categories();
	$current_category_slug = '';
	foreach ( $product_categories as $cat ) {
		if ( ! empty( $cat->slug ) && 'todo' !== $cat->slug && has_term( $cat->slug, 'product_cat', get_the_ID() ) ) {
			$current_category_slug = $cat->slug;
			break;
		}
	}
	?>

	<div id="product-<?php the_ID(); ?>" <?php wc_product_class( 'prs-shop-layout prs-product-page', $product ); ?>>

		<aside class="prs-product-left-nav" aria-label="<?php echo esc_attr__( 'Categorías de producto', 'palancia-shop' ); ?>">
			<?php include locate_template( 'template-parts/shop-nav.php' ); ?>
		</aside>

		<div class="prs-product-gallery-wrapper">
			<?php do_action( 'woocommerce_before_single_product_summary' ); ?>
		</div>

		<div class="prs-product-info">
			<?php do_action( 'woocommerce_single_product_summary' ); ?>
		</div>

		<div class="prs-product-extra">
			<?php do_action( 'woocommerce_after_single_product_summary' ); ?>
		</div>

	</div>

	<?php do_action( 'woocommerce_after_main_content' ); ?>

	<?php
	do_action( 'woocommerce_after_single_product' );

endwhile;

get_footer( 'shop' );

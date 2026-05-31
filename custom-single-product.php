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

	<div id="product-<?php the_ID(); ?>" <?php wc_product_class( 'prs-product-page', $product ); ?>>

		<aside class="prs-product-left-nav">
			<ul>
				<?php
				// Recuperamos la talla si venía en la URL (aunque estemos en single product)
				$current_size_slug = isset( $_GET['size'] ) ? sanitize_text_field( $_GET['size'] ) : '';
				$final_categories = prs_get_sorted_product_categories();

				foreach ( $final_categories as $cat ) :
					$url = ( strtolower( $cat->slug ) === 'todo' )
						? home_url( '/' )
						: get_term_link( $cat );

					if ( is_wp_error( $url ) ) {
						continue;
					}

					if ( ! empty( $current_size_slug ) ) {
						$url = add_query_arg( 'size', $current_size_slug, $url );
					}
					?>
					<li>
						<a href="<?php echo esc_url( $url ); ?>" data-category-slug="<?php echo esc_attr( $cat->slug ); ?>">
							<?php echo esc_html( $cat->name ); ?>
						</a>
					</li>
					<?php if ( 'palancia-merch' === $cat->slug ) : ?>
						<li class="filter-divider" aria-hidden="true"></li>
					<?php endif; ?>
				<?php endforeach; ?>
			</ul>
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

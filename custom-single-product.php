<?php
/**
 * Plantilla custom para la ficha de producto, replicando el layout actual
 * con menú lateral, galería custom y columna de info.
 */

defined( 'ABSPATH' ) || exit;

get_header();

do_action( 'woocommerce_before_single_product' );

if ( post_password_required() ) {
	echo get_the_password_form();
	get_footer();
	return;
}

?>

<div id="product-<?php the_ID(); ?>" <?php wc_product_class( 'prs-product-page', wc_get_product() ); ?>>

	<!-- COLUMNA IZQUIERDA: MENÚ / FILTRO -->
	<aside class="prs-product-left-nav">
		<ul>
			<?php
			$product_categories = get_terms(
				[
					'taxonomy'   => 'product_cat',
					'hide_empty' => true,
				]
			);

			$excluded_slugs     = [ 'tops', 'bottoms', 'accesorios' ];
			$product_categories = array_values(
				array_filter(
					$product_categories,
					function ( $cat ) use ( $excluded_slugs ) {
						return ! in_array( strtolower( $cat->slug ), $excluded_slugs, true );
					}
				)
			);

			$priority_order   = [ 'todo', 'chaquetas', 'chalecos', 'sudaderas', 'jerseis', 'tracksuits', 'pantalones', 'camisetas', 'bolsos', 'gafas', 'gorras' ]; // slugs en minúsculas
			$final_categories = [];

			foreach ( $priority_order as $slug ) {
				foreach ( $product_categories as $cat ) {
					if ( strtolower( $cat->slug ) === $slug ) {
						$final_categories[] = $cat;
					}
				}
			}

			foreach ( $product_categories as $cat ) {
				if ( ! in_array( $cat, $final_categories, true ) ) {
					$final_categories[] = $cat;
				}
			}

			$product_categories = $final_categories;

			foreach ( $product_categories as $cat ) :
				$url = ( strtolower( $cat->slug ) === 'todo' )
					? home_url( '/' )
					: get_term_link( $cat );
				?>
				<li>
					<a href="<?php echo esc_url( $url ); ?>"
						data-category-slug="<?php echo esc_attr( $cat->slug ); ?>">
						<?php echo esc_html( $cat->name ); ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</aside>

	<!-- COLUMNA CENTRAL: GALERÍA -->
	<div class="prs-product-gallery-wrapper">
		<?php do_action( 'woocommerce_before_single_product_summary' ); ?>
	</div>

	<!-- COLUMNA DERECHA: INFO -->
	<div class="prs-product-info">
		<?php do_action( 'woocommerce_single_product_summary' ); ?>
	</div>

	<!-- EXTRA ABAJO -->
	<div class="prs-product-extra">
		<?php do_action( 'woocommerce_after_single_product_summary' ); ?>
	</div>

</div>

<?php do_action( 'woocommerce_after_single_product' ); ?>

<?php get_footer(); ?>

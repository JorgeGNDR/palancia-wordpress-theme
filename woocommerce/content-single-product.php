<?php
defined( 'ABSPATH' ) || exit;

global $product;

do_action( 'woocommerce_before_single_product' );

if ( post_password_required() ) {
	echo get_the_password_form();
	return;
}
?>

<div id="product-<?php the_ID(); ?>" <?php wc_product_class( 'prs-product-page', $product ); ?>>

	<!-- COLUMNA IZQUIERDA: MENÚ / FILTRO -->
	<aside class="prs-product-left-nav">
		<ul>
			<?php
			// Obtener categorías
			$product_categories = get_terms([
				'taxonomy'   => 'product_cat',
				'hide_empty' => true,
			]);

			// Orden personalizado: ALL, TOPS, BOTTOMS, ACCESORIES primero
			$priority_order = ['all', 'tops', 'bottoms', 'accesories']; // Slugs en minúsculas

			$final_categories = [];

			// 1. Meter primero las categorías prioritarias
			foreach ($priority_order as $slug) {
				foreach ($product_categories as $cat) {
					if (strtolower($cat->slug) === $slug) {
						$final_categories[] = $cat;
					}
				}
			}

			// 2. Meter el resto de categorías sin duplicar
			foreach ($product_categories as $cat) {
				if (!in_array($cat, $final_categories, true)) {
					$final_categories[] = $cat;
				}
			}

			// Sustituimos product_categories por final_categories para el foreach de abajo
			$product_categories = $final_categories;


			 // $product_categories ya viene de get_terms() con tu orden
			foreach ( $product_categories as $cat ) :
				if ($slug === 'all') {
					$url = home_url( '/' );
				 } else {
					$url = get_term_link( $cat );
				}
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
		<?php
		do_action( 'woocommerce_before_single_product_summary' ); // aquí entra nuestra galería custom
		?>
	</div>

	<!-- COLUMNA DERECHA: INFO -->
	<div class="prs-product-info">
		<?php
		do_action( 'woocommerce_single_product_summary' );
		?>
	</div>

	<!-- EXTRA ABAJO (si quisieras usarlo algún día) -->
	<div class="prs-product-extra">
		<?php
		do_action( 'woocommerce_after_single_product_summary' );
		?>
	</div>

</div>

<?php do_action( 'woocommerce_after_single_product' ); ?>

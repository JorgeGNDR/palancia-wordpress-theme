<?php
/**
 * Página genérica
 *
 * Renderiza el contenido de páginas (incluye las de WooCommerce como /cart).
 *
 * @package palancia-retro-shop
 */

get_header();
?>

<main id="primary" class="site-main" style="padding: 40px 0;">
	<div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
		<?php
		if ( have_posts() ) {
			while ( have_posts() ) {
				the_post();
				if ( function_exists( 'is_cart' ) && is_cart() ) {
					// Fuerza el carrito aunque el contenido de la página no tenga el shortcode.
					echo do_shortcode( '[woocommerce_cart]' );
				} else {
					the_content();
				}
			}
		}
		?>
	</div>
</main>

<?php
get_footer();

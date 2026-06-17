<?php
/**
 * Generic page template.
 *
 * @package palancia-shop
 */

get_header();
?>

<section id="primary" class="page-content">
  <div class="container">
    <?php
    if ( have_posts() ) {
        while ( have_posts() ) {
            the_post();
            if ( function_exists( 'is_cart' ) && is_cart() ) {
                echo do_shortcode( '[woocommerce_cart]' );
            } else {
                the_content();
            }
        }
    }
    ?>
  </div>
</section>

<?php
get_footer();

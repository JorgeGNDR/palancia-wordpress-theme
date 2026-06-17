<?php
get_header();
?>

<section class="page-content">
  <div class="container">
    <h2>Error 404</h2>
    <p>No encontramos esa página.</p>
    <a class="pal-cart-checkout" href="<?php echo esc_url( home_url( '/' ) ); ?>">Volver</a>
  </div>
</section>

<?php
get_footer();

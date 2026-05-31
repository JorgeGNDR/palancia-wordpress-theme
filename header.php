<?php
/**
 * Header
 */
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<header class="site-header">
  <?php if ( is_front_page() || is_tax( 'product_cat' ) ) : ?>
    <button id="mobile-filter-toggle" class="mobile-filter-btn" aria-label="Filtrar categorías">
      <span class="mobile-filter-icon" aria-hidden="true"></span>
    </button>
  <?php endif; ?>
  <div class="logo">
    <a href="<?php echo esc_url(home_url('/')); ?>">
      <?php
      if ( function_exists( 'the_custom_logo' ) && has_custom_logo() ) {
          the_custom_logo();
      } else {
          // Fallback si no hay logo en el personalizador
          $fallback_logo = get_stylesheet_directory_uri() . '/assets/icons/NEO P VALERIAN BLUE.png';
          ?>
          <img src="<?php echo esc_url( $fallback_logo ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" class="site-logo-fallback">
          <?php
      }
      ?>
    </a>
  </div>

  <?php if ( function_exists( 'WC' ) && function_exists( 'wc_get_cart_url' ) ) : ?>
    <?php $cart_count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0; ?>
    <div class="header-cart">
      <a href="<?php echo esc_url( wc_get_cart_url() ); ?>" aria-label="Ir al carrito">
        <span class="cart-count-inline"><?php echo esc_html( $cart_count ); ?></span>
        <span class="cart-icon" aria-hidden="true"></span>
        <span class="screen-reader-text">Carrito</span>
      </a>
    </div>
  <?php endif; ?>
</header>

<main class="site-main">

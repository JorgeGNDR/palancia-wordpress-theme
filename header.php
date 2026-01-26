<?php
/**
 * Header
 */
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=menu">
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<header class="site-header">
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
        <span class="cart-icon" aria-hidden="true"></span>
        <span class="screen-reader-text">Carrito</span>
        <span class="cart-count">
          <?php echo esc_html( $cart_count ); ?>
        </span>
      </a>
    </div>
  <?php endif; ?>
</header>

<main class="site-main">

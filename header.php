<?php
/**
 * Header
 */
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<header class="site-header">
  <div class="logo">
    <a href="<?php echo esc_url(home_url('/')); ?>">
      <?php
      if ( function_exists('the_custom_logo') && has_custom_logo() ) {
          the_custom_logo();
      } else {
          // Fallback si no has subido logo aún
          bloginfo('name');
      }
      ?>
    </a>
  </div>

  <div class="header-cart">
    <a href="<?php echo wc_get_cart_url(); ?>">
      <img src="<?php echo get_template_directory_uri(); ?>/assets/icons/carrito.png" alt="Carrito" class="cart-icon">
      <span class="cart-count">
        <?php echo WC()->cart->get_cart_contents_count(); ?>
      </span>
    </a>
  </div>
</header>

<main class="site-main">

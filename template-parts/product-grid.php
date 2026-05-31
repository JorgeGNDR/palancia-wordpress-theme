<?php
/**
 * Product grid + filters (shared by home and category pages).
 *
 * Expects:
 * - $product_categories (array of WP_Term)
 * - $current_category_slug (string)
 */

$current_category_slug = isset( $current_category_slug ) ? (string) $current_category_slug : '';
?>

<section class="home-products">
  <div class="prs-shop-layout home-products-container">
    <nav id="mobile-filter-menu" class="mobile-filter-menu" aria-label="<?php echo esc_attr__( 'Filtros de producto', 'palancia-shop' ); ?>">
      <?php include locate_template( 'template-parts/shop-nav.php' ); ?>
    </nav>

    <aside class="product-filters" aria-label="<?php echo esc_attr__( 'Filtros de producto', 'palancia-shop' ); ?>">
      <?php include locate_template( 'template-parts/shop-nav.php' ); ?>
    </aside>

    <div class="home-products-grid">
      <?php
      $grid_html = function_exists( 'prs_render_products_grid' )
        ? prs_render_products_grid( $current_category_slug )
        : '';

      echo $grid_html ? $grid_html : '<p>' . esc_html__( 'No hay productos en esta categoria.', 'palancia-shop' ) . '</p>';
      ?>
    </div>

    <div class="home-products-spacer" aria-hidden="true"></div>
  </div>
</section>

<?php
/**
 * Product grid + filters (shared by home and category pages).
 *
 * Expects:
 * - $product_categories (array of WP_Term)
 * - $current_category_slug (string)
 */

$current_category_slug = isset( $current_category_slug ) ? (string) $current_category_slug : '';
$current_size_slug     = isset( $current_size_slug ) ? (string) $current_size_slug : ( isset( $_GET['size'] ) ? sanitize_text_field( wp_unslash( $_GET['size'] ) ) : '' );
$current_stock_filter  = isset( $_GET['soldout'] ) && 'hide' === sanitize_title( wp_unslash( $_GET['soldout'] ) ) ? 'hide' : 'show';
$size_terms            = function_exists( 'prs_get_available_size_terms' ) ? prs_get_available_size_terms() : [];
$has_size_filter       = ! empty( $size_terms );
?>

<section class="home-products">
  <div class="prs-shop-layout home-products-container">
    <nav id="mobile-filter-menu" class="mobile-filter-menu" aria-label="<?php echo esc_attr__( 'Filtros de producto', 'palancia-shop' ); ?>">
      <?php include locate_template( 'template-parts/shop-nav.php' ); ?>
      <?php if ( $has_size_filter ) : ?>
        <button class="product-filter-trigger" type="button" aria-expanded="false" aria-controls="product-size-filter-modal">
          <?php echo esc_html__( 'Filtro', 'palancia-shop' ); ?>
        </button>
      <?php endif; ?>
    </nav>

    <aside class="product-filters" aria-label="<?php echo esc_attr__( 'Filtros de producto', 'palancia-shop' ); ?>">
      <?php include locate_template( 'template-parts/shop-nav.php' ); ?>
      <?php if ( $has_size_filter ) : ?>
        <button class="product-filter-trigger" type="button" aria-expanded="false" aria-controls="product-size-filter-modal">
          <?php echo esc_html__( 'Filtro', 'palancia-shop' ); ?>
        </button>
      <?php endif; ?>
    </aside>

    <div class="home-products-grid">
      <?php
      $grid_html = function_exists( 'prs_render_products_grid' )
        ? prs_render_products_grid( $current_category_slug, 0, $current_size_slug, $current_stock_filter )
        : '';

      echo $grid_html ? $grid_html : '<p>' . esc_html__( 'No hay productos en esta categoria.', 'palancia-shop' ) . '</p>';
      ?>
    </div>

    <div class="home-products-spacer" aria-hidden="true"></div>
  </div>

  <?php include locate_template( 'template-parts/size-filter-modal.php' ); ?>
</section>

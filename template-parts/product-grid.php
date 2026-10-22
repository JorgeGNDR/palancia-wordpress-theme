<?php
/**
 * Product grid + filters (shared by home and category pages).
 *
 * Expects:
 * - $product_categories (array of WP_Term)
 * - $current_category_slug (string)
 */

$current_category_slug = isset( $current_category_slug ) ? (string) $current_category_slug : '';
$catalog_state        = prs_catalog_page_state( $current_category_slug );
$current_size_slug    = $catalog_state['size'];
$current_stock_filter = $catalog_state['soldout'];
$size_terms            = function_exists( 'prs_get_available_size_terms' ) ? prs_get_available_size_terms() : [];
$has_size_filter       = ! empty( $size_terms );
$current_sort          = $catalog_state['sort'];
?>

<section class="home-products" data-catalog="<?php echo esc_attr( wp_json_encode( [ 'state' => $catalog_state, 'homeUrl' => home_url( '/' ), 'routeKeys' => [ 'product_cat', 'post_type', 'page_id', 'p', 'pagename', 'taxonomy', 'term', 'paged' ] ] ) ); ?>">
  <p class="prs-catalog-error" data-catalog-error role="alert" hidden><?php esc_html_e( 'No se han podido cargar los productos. Vuelve a intentarlo.', 'palancia-shop' ); ?></p>
  <div class="prs-shop-layout home-products-container">
    <nav id="mobile-filter-menu" class="mobile-filter-menu" aria-label="<?php echo esc_attr__( 'Filtros de producto', 'palancia-shop' ); ?>">
      <?php include locate_template( 'template-parts/shop-nav.php' ); ?>
      <div class="product-menu-actions">
      <?php if ( $has_size_filter ) : ?>
        <button class="product-filter-trigger" type="button" aria-expanded="false" aria-controls="product-size-filter-modal">
          <?php echo esc_html__( 'Filtrar', 'palancia-shop' ); ?>
        </button>
      <?php endif; ?>
      <?php include locate_template( 'template-parts/product-sort.php' ); ?>
      </div>
    </nav>

    <aside class="product-filters" aria-label="<?php echo esc_attr__( 'Filtros de producto', 'palancia-shop' ); ?>">
      <?php include locate_template( 'template-parts/shop-nav.php' ); ?>
      <div class="product-menu-actions">
      <?php if ( $has_size_filter ) : ?>
        <button class="product-filter-trigger" type="button" aria-expanded="false" aria-controls="product-size-filter-modal">
          <?php echo esc_html__( 'Filtrar', 'palancia-shop' ); ?>
        </button>
      <?php endif; ?>
      <?php include locate_template( 'template-parts/product-sort.php' ); ?>
      </div>
    </aside>

    <div class="home-products-grid">
      <?php
      $grid_html = function_exists( 'prs_render_products_grid' )
        ? prs_render_products_grid( $current_category_slug, 0, $current_size_slug, $current_stock_filter, $current_sort )
        : '';

      echo $grid_html ? $grid_html : '<p>' . esc_html__( 'No hay productos en esta categoria.', 'palancia-shop' ) . '</p>';
      ?>
    </div>

    <div class="home-products-spacer" aria-hidden="true"></div>
  </div>

  <?php include locate_template( 'template-parts/size-filter-modal.php' ); ?>
  <?php include locate_template( 'template-parts/product-sort-modal.php' ); ?>
</section>

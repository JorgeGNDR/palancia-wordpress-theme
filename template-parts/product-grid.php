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
  <div class="home-products-container">
    <nav id="mobile-filter-menu" class="mobile-filter-menu">
      <ul>
        <?php foreach ( $product_categories as $category ) : ?>
          <?php $cat_link = get_term_link( $category ); ?>
          <li>
            <a href="<?php echo esc_url( $cat_link ); ?>"
              data-category-slug="<?php echo esc_attr( $category->slug ); ?>">
              <?php echo esc_html( $category->name ); ?>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </nav>

    <!-- Columna de filtros -->
    <aside class="product-filters">
      <ul>
        <?php foreach ( $product_categories as $category ) : ?>
          <?php $cat_link = get_term_link( $category ); ?>
          <li>
            <a href="<?php echo esc_url( $cat_link ); ?>"
              data-category-slug="<?php echo esc_attr( $category->slug ); ?>">
              <?php echo esc_html( $category->name ); ?>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </aside>

    <!-- Grid de productos -->
    <div class="home-products-grid">
      <?php
      $grid_html = function_exists( 'prs_render_products_grid' )
        ? prs_render_products_grid( $current_category_slug )
        : '';
      echo $grid_html ? $grid_html : '<p>No hay productos en esta categoria.</p>';
      ?>
    </div>

    <!-- Tercera columna vacía para mantener la misma estructura -->
    <div></div>
  </div>
</section>

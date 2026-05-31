<?php
/**
 * Product grid + filters (shared by home and category pages).
 *
 * Expects:
 * - $product_categories (array of WP_Term)
 * - $current_category_slug (string)
 */

$current_category_slug = isset( $current_category_slug ) ? (string) $current_category_slug : '';
$current_size_slug     = isset( $_GET['size'] ) ? sanitize_title( wp_unslash( $_GET['size'] ) ) : '';

$product_sizes = get_terms( [
    'taxonomy'   => 'pa_talla',
    'hide_empty' => true,
] );

$render_filter_list = function() use ( $product_categories, $product_sizes, $current_category_slug, $current_size_slug ) {
    ?>
    <ul>
      <?php foreach ( $product_categories as $category ) : ?>
        <?php
        $cat_link = ( 'todo' === $category->slug )
            ? home_url( '/' )
            : get_term_link( $category );

        if ( is_wp_error( $cat_link ) ) {
            continue;
        }

        if ( ! empty( $current_size_slug ) ) {
            $cat_link = add_query_arg( 'size', $current_size_slug, $cat_link );
        }
        ?>
        <li>
          <a href="<?php echo esc_url( $cat_link ); ?>"
             class="<?php echo ( $current_category_slug === $category->slug || ( '' === $current_category_slug && 'todo' === $category->slug ) ) ? 'is-active' : ''; ?>"
             data-category-slug="<?php echo esc_attr( $category->slug ); ?>">
            <?php echo esc_html( $category->name ); ?>
          </a>
        </li>
        <?php if ( 'palancia-merch' === $category->slug ) : ?>
          <li class="filter-divider" aria-hidden="true"></li>
        <?php endif; ?>
      <?php endforeach; ?>

      <?php if ( ! empty( $product_sizes ) && ! is_wp_error( $product_sizes ) ) : ?>
        <li class="filter-divider" aria-hidden="true"></li>
        <?php foreach ( $product_sizes as $size ) : ?>
          <?php
          $size_link = ( $current_size_slug === $size->slug )
              ? remove_query_arg( 'size' )
              : add_query_arg( 'size', $size->slug );
          ?>
          <li>
            <a href="<?php echo esc_url( $size_link ); ?>"
               data-size-slug="<?php echo esc_attr( $size->slug ); ?>"
               class="<?php echo ( $current_size_slug === $size->slug ) ? 'is-active' : ''; ?>">
              <?php echo esc_html( $size->name ); ?>
            </a>
          </li>
        <?php endforeach; ?>
      <?php endif; ?>
    </ul>
    <?php
};
?>

<section class="home-products">
  <div class="home-products-container">
    <nav id="mobile-filter-menu" class="mobile-filter-menu" aria-label="<?php echo esc_attr__( 'Filtros de producto', 'palancia-shop' ); ?>">
      <?php $render_filter_list(); ?>
    </nav>

    <aside class="product-filters" aria-label="<?php echo esc_attr__( 'Filtros de producto', 'palancia-shop' ); ?>">
      <?php $render_filter_list(); ?>
    </aside>

    <div class="home-products-grid">
      <?php
      $grid_html = function_exists( 'prs_render_products_grid' )
        ? prs_render_products_grid( $current_category_slug, 0, $current_size_slug )
        : '';

      echo $grid_html ? $grid_html : '<p>' . esc_html__( 'No hay productos en esta categoria.', 'palancia-shop' ) . '</p>';
      ?>
    </div>

    <div class="home-products-spacer" aria-hidden="true"></div>
  </div>
</section>

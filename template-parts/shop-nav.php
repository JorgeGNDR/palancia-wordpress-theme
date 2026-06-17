<?php
/**
 * Shared shop category navigation.
 *
 * Expects:
 * - $product_categories (array of WP_Term-like objects)
 * - $current_category_slug (string)
 */

$product_categories    = isset( $product_categories ) && is_array( $product_categories ) ? $product_categories : [];
$current_category_slug = isset( $current_category_slug ) ? (string) $current_category_slug : '';
$size_terms            = function_exists( 'prs_get_available_size_terms' ) ? prs_get_available_size_terms() : [];
$selected_sizes        = isset( $_GET['size'] ) ? array_filter( array_map( 'sanitize_title', explode( ',', sanitize_text_field( wp_unslash( $_GET['size'] ) ) ) ) ) : [];
$stock_filter          = isset( $_GET['soldout'] ) && 'hide' === sanitize_title( wp_unslash( $_GET['soldout'] ) ) ? 'hide' : 'show';
?>

<ul>
  <?php foreach ( $product_categories as $category ) : ?>
    <?php
    if ( empty( $category->slug ) || empty( $category->name ) ) {
        continue;
    }

    $cat_link = ( 'todo' === $category->slug )
        ? home_url( '/' )
        : get_term_link( $category );

    if ( is_wp_error( $cat_link ) ) {
        continue;
    }

    $is_active = $current_category_slug === $category->slug || ( '' === $current_category_slug && 'todo' === $category->slug );
    ?>
    <li>
      <a href="<?php echo esc_url( $cat_link ); ?>"
         class="<?php echo $is_active ? 'is-active' : ''; ?>"
         data-category-slug="<?php echo esc_attr( $category->slug ); ?>">
        <?php echo esc_html( $category->name ); ?>
      </a>
    </li>
  <?php endforeach; ?>
</ul>

<?php if ( ! empty( $size_terms ) ) : ?>
  <div class="product-size-filter" aria-label="<?php echo esc_attr__( 'Filtro por talla', 'palancia-shop' ); ?>">
    <p class="product-size-filter-title"><?php echo esc_html__( 'Filtro', 'palancia-shop' ); ?></p>

    <div class="product-stock-filter">
      <span><?php echo esc_html__( 'Sold out', 'palancia-shop' ); ?></span>
      <div class="product-stock-actions">
        <button class="<?php echo 'show' === $stock_filter ? 'is-active' : ''; ?>" type="button" data-stock-filter="show"><?php echo esc_html__( 'Show', 'palancia-shop' ); ?></button>
        <button class="<?php echo 'hide' === $stock_filter ? 'is-active' : ''; ?>" type="button" data-stock-filter="hide"><?php echo esc_html__( 'Hide', 'palancia-shop' ); ?></button>
      </div>
    </div>

    <div class="product-size-options">
      <span><?php echo esc_html__( 'In-stock', 'palancia-shop' ); ?></span>
      <div class="product-size-buttons">
        <?php foreach ( $size_terms as $term ) : ?>
          <button class="<?php echo in_array( $term->slug, $selected_sizes, true ) ? 'is-active' : ''; ?>" type="button" data-size-slug="<?php echo esc_attr( $term->slug ); ?>">
            <?php echo esc_html( $term->name ); ?>
          </button>
        <?php endforeach; ?>
      </div>
    </div>

    <button class="product-size-clear" type="button"><?php echo esc_html__( 'Clear', 'palancia-shop' ); ?></button>
  </div>
<?php endif; ?>

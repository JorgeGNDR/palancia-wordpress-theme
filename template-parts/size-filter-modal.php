<?php
/**
 * Size filter trigger and modal for product grids.
 */

$size_terms     = function_exists( 'prs_get_available_size_terms' ) ? prs_get_available_size_terms() : [];
$selected_sizes = isset( $_GET['size'] ) ? array_filter( array_map( 'sanitize_title', explode( ',', sanitize_text_field( wp_unslash( $_GET['size'] ) ) ) ) ) : [];
$stock_filter   = isset( $_GET['soldout'] ) && 'hide' === sanitize_title( wp_unslash( $_GET['soldout'] ) ) ? 'hide' : 'show';
?>

<?php if ( ! empty( $size_terms ) ) : ?>
  <button class="product-filter-trigger" type="button" aria-expanded="false" aria-controls="product-size-filter-modal">
    <?php echo esc_html__( 'Filtro', 'palancia-shop' ); ?>
  </button>

  <div id="product-size-filter-modal" class="product-size-filter-modal" aria-hidden="true">
    <div class="product-size-filter-backdrop" data-filter-close></div>
    <div class="product-size-filter" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr__( 'Filtro por talla', 'palancia-shop' ); ?>">
      <button class="product-filter-close" type="button" data-filter-close><?php echo esc_html__( 'Close', 'palancia-shop' ); ?></button>

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
  </div>
<?php endif; ?>

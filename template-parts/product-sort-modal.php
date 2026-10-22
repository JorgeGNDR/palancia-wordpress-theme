<?php defined( 'ABSPATH' ) || exit; ?>
<div id="product-sort-modal" class="product-size-filter-modal" aria-hidden="true">
  <div class="product-size-filter-backdrop" data-panel-close></div>
  <div class="product-size-filter product-sort-panel" role="dialog" aria-modal="true" aria-labelledby="product-sort-title">
    <button type="button" class="product-filter-close" data-panel-close><?php esc_html_e( 'Cerrar', 'palancia-shop' ); ?></button>
    <h2 id="product-sort-title"><?php esc_html_e( 'Ordenar por', 'palancia-shop' ); ?></h2>
    <div class="product-sort-options" role="group" aria-label="<?php esc_attr_e( 'Ordenar productos por', 'palancia-shop' ); ?>">
      <?php foreach ( [
          'categories' => __( 'Categorías', 'palancia-shop' ),
          'latest' => __( 'Últimas subidas', 'palancia-shop' ),
          'price-asc' => __( 'Precio: de menor a mayor', 'palancia-shop' ),
          'price-desc' => __( 'Precio: de mayor a menor', 'palancia-shop' ),
      ] as $value => $label ) : ?>
        <button type="button" class="product-sort-option" data-sort="<?php echo esc_attr( $value ); ?>" aria-pressed="<?php echo $current_sort === $value ? 'true' : 'false'; ?>"><?php echo esc_html( $label ); ?></button>
      <?php endforeach; ?>
    </div>
  </div>
</div>

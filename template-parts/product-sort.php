<?php defined( 'ABSPATH' ) || exit; ?>
<details class="product-sort">
  <summary><?php esc_html_e( 'Ordenar por', 'palancia-shop' ); ?></summary>
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
</details>

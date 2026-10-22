<?php defined( 'ABSPATH' ) || exit; ?>
<details class="product-sort">
  <summary><?php esc_html_e( 'Ordenar por', 'palancia-shop' ); ?></summary>
  <select class="product-sort-select" aria-label="<?php esc_attr_e( 'Ordenar productos por', 'palancia-shop' ); ?>">
    <?php foreach ( [
        'categories' => __( 'Categorías', 'palancia-shop' ),
        'latest' => __( 'Últimas subidas', 'palancia-shop' ),
        'price-asc' => __( 'Precio: de menor a mayor', 'palancia-shop' ),
        'price-desc' => __( 'Precio: de mayor a menor', 'palancia-shop' ),
    ] as $value => $label ) : ?>
      <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current_sort, $value ); ?>><?php echo esc_html( $label ); ?></option>
    <?php endforeach; ?>
  </select>
</details>

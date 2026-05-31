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

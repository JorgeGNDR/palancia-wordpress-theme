<?php
/**
 * Plantilla Home: galeria de productos con filtros
 */

get_header();

if ( ! class_exists('WooCommerce') ) {
    echo '<p>Instala y activa WooCommerce para mostrar productos.</p>';
    get_footer();
    exit;
}

// Obtener categorias para los filtros (igual que en la home)
$product_categories = get_terms( [
    'taxonomy'   => 'product_cat',
    'hide_empty' => true,
] );

// Excluir categorias que ya no deben mostrarse en el menu
$excluded_slugs   = [ 'tops', 'bottoms', 'accesorios' ];
$product_categories = array_values( array_filter( $product_categories, function ( $cat ) use ( $excluded_slugs ) {
    return ! in_array( strtolower( $cat->slug ), $excluded_slugs, true );
} ) );

// Orden personalizado solicitado
$priority_order   = [ 'todo', 'chaquetas', 'chalecos', 'sudaderas', 'jerseis', 'tracksuits', 'pantalones', 'camisetas', 'bolsos', 'gafas', 'gorras' ]; // slugs en minusculas
$final_categories = [];

// 1. Primero las prioritarias
foreach ( $priority_order as $slug ) {
    foreach ( $product_categories as $cat ) {
        if ( strtolower( $cat->slug ) === $slug ) {
            $final_categories[] = $cat;
        }
    }
}

// 2. Luego el resto sin duplicar
foreach ( $product_categories as $cat ) {
    if ( ! in_array( $cat, $final_categories, true ) ) {
        $final_categories[] = $cat;
    }
}

$product_categories = $final_categories;

?>

<section class="home-products">
  <div class="home-products-container">
    <!-- Columna de filtros -->
    <aside class="product-filters">
      <ul>
        <?php foreach ( $product_categories as $category ) : ?>
          <li>
            <a href="<?php echo esc_url( get_term_link( $category ) ); ?>"
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
      $grid_html = prs_render_products_grid();
      echo $grid_html ? $grid_html : '<p>No hay productos todavia.</p>';
      ?>
    </div>

    <!-- Columna vacia para centrar el grid -->
    <div class="empty-column"></div>
  </div>
</section>

<?php
get_footer();

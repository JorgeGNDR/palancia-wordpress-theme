<?php
/**
 * Plantilla para categorias de producto (/collections/{slug})
 * Mismo layout que la home: filtros a la izquierda + grid en el centro.
 *
 * @package WooCommerce/Templates
 * @version 4.7.0
 */

get_header();

// Comprobamos WooCommerce.
if ( ! class_exists( 'WooCommerce' ) ) {
    echo '<p>Instala y activa WooCommerce para mostrar productos.</p>';
    get_footer();
    exit;
}

// Categoria actual.
$current_term = get_queried_object();

// Si la categoria es TODO, redirigimos a la home.
if ( isset( $current_term->slug ) && $current_term->slug === 'todo' ) {
    wp_safe_redirect( home_url( '/' ), 301 );
    exit;
}

// Obtener categorias para los filtros (igual que en la home).
$product_categories = get_terms( [
    'taxonomy'   => 'product_cat',
    'hide_empty' => true,
] );

// Excluir categorias que ya no deben mostrarse en el menu.
$excluded_slugs     = [ 'tops', 'bottoms', 'accesorios' ];
$product_categories = array_values( array_filter( $product_categories, function ( $cat ) use ( $excluded_slugs ) {
    return ! in_array( strtolower( $cat->slug ), $excluded_slugs, true );
} ) );

// Orden personalizado solicitado.
$priority_order   = [ 'todo', 'chaquetas', 'chalecos', 'sudaderas', 'jerseis', 'tracksuits', 'pantalones', 'camisetas', 'bolsos', 'gafas', 'gorras' ]; // slugs en minusculas
$final_categories = [];

// 1. Primero las prioritarias.
foreach ( $priority_order as $slug ) {
    foreach ( $product_categories as $cat ) {
        if ( strtolower( $cat->slug ) === $slug ) {
            $final_categories[] = $cat;
        }
    }
}

// 2. Luego el resto sin duplicar.
foreach ( $product_categories as $cat ) {
    if ( ! in_array( $cat, $final_categories, true ) ) {
        $final_categories[] = $cat;
    }
}

$product_categories = $final_categories;

?>

<section class="home-products">
  <div class="home-products-container">

    <!-- Botón menú filtro móvil -->
    <button id="mobile-filter-toggle" class="mobile-filter-btn" aria-label="Filtrar categorías">
        <span class="mobile-filter-icon material-symbols-outlined" aria-hidden="true">menu</span>
    </button>
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
      // Reutilizamos el helper de la home para que respete el orden por precio.
      $grid_html = function_exists( 'prs_render_products_grid' ) ? prs_render_products_grid( $current_term->slug ) : '';
      echo $grid_html ? $grid_html : '<p>No hay productos en esta categoria.</p>';
      ?>
    </div>

    <!-- Tercera columna vacia para mantener la misma estructura -->
    <div></div>

  </div>
</section>

<?php
get_footer();

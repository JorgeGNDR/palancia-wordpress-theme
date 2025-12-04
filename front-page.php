<?php
/**
 * Plantilla Home: galería de productos con filtros
 */

get_header();

if ( ! class_exists('WooCommerce') ) {
    echo '<p>Instala y activa WooCommerce para mostrar productos.</p>';
    get_footer();
    exit;
}

// Query de productos
$args = [
    'post_type'      => 'product',
    'posts_per_page' => 60,
    'post_status'    => 'publish',
];

$loop = new WP_Query($args);

// Obtener categorías para los filtros (igual que en la home)
$product_categories = get_terms( [
    'taxonomy'   => 'product_cat',
    'hide_empty' => true,
] );

// Orden personalizado: TODO, TOPS, BOTTOMS, ACCESORIOS primero
$priority_order   = [ 'todo', 'tops', 'bottoms', 'accesorios' ]; // slugs en minúsculas
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
      <?php if ( $loop->have_posts() ) : ?>
        <?php while ( $loop->have_posts() ) : $loop->the_post(); 
          global $product;
          $thumb_id = get_post_thumbnail_id();
          $img = wp_get_attachment_image_src( $thumb_id, 'large' );
          $img_url = $img ? $img[0] : wc_placeholder_img_src();
          $url = get_permalink();
        ?>
          <a href="<?php echo esc_url( $url ); ?>" class="product-item">
            <img src="<?php echo esc_url($img_url); ?>" alt="<?php the_title_attribute(); ?>">
            <div class="product-overlay">
              <span><?php the_title(); ?></span>
            </div>
          </a>
        <?php endwhile; ?>
        <?php wp_reset_postdata(); ?>
      <?php else : ?>
        <p>No hay productos todavía.</p>
      <?php endif; ?>
    </div>

    <!-- Columna vacía para centrar el grid -->
    <div class="empty-column"></div>
  </div>
</section>

<?php
get_footer();

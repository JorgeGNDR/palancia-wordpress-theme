<?php

// Cargar CSS y JS del tema
function prs_enqueue_assets() {
    // CSS principal
    wp_enqueue_style(
        'palancia-retro-shop-style',
        get_stylesheet_uri(),
        [],
        filemtime( get_stylesheet_directory() . '/style.css' )
    );

    // JS del slider solo en ficha de producto
    if ( is_product() ) {
        wp_enqueue_script(
            'prs-product-gallery',
            get_stylesheet_directory_uri() . '/assets/js/product-gallery.js',
            [],
            filemtime( get_stylesheet_directory() . '/assets/js/product-gallery.js' ),
            true
        );
    }
}
add_action( 'wp_enqueue_scripts', 'prs_enqueue_assets' );

// Encolar el script de filtrado de productos con AJAX
function prs_enqueue_filter_script() {
    wp_enqueue_script(
        'prs-filter-products',
        get_stylesheet_directory_uri() . '/assets/js/filter-products.js',
        ['jquery'],
        filemtime( get_stylesheet_directory() . '/assets/js/filter-products.js' ),
        true
    );

    wp_localize_script('prs-filter-products', 'prsFilterProducts', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('prs_filter_nonce'),
    ]);
}
add_action('wp_enqueue_scripts', 'prs_enqueue_filter_script');

// Soporte de tema
function prs_setup_theme() {
    add_theme_support( 'woocommerce' );
    add_theme_support( 'title-tag' );
    add_theme_support( 'custom-logo', [
        'height'      => 80,
        'width'       => 80,
        'flex-height' => true,
        'flex-width'  => true,
    ] );
}
add_action( 'after_setup_theme', 'prs_setup_theme' );


// ------------ LIMPIEZA FICHA PRODUCTO ------------ //

// Limpiar cosas del resumen y tabs
function prs_cleanup_single_summary_hooks() {
    // Quitar rating, meta, compartir
    remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10 );
    remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
    remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_sharing', 50 );

    // Quitar descripción corta por defecto (excerpt)
    remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 );

    // Quitar tabs, upsells y relacionados de abajo
    remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );
    remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 );
    remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );

    // Quitar breadcrumbs (Inicio / Categoría / Producto)
    remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );

    // Quitar sidebar de WooCommerce (Buscar, Páginas, Archivos, Categorías)
    remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );
}
add_action( 'init', 'prs_cleanup_single_summary_hooks' );

// Mostrar talla debajo del título (atributo pa_talla)
function prs_show_product_size() {
    global $product;

    if ( ! $product ) {
        return;
    }

    $size_terms = wc_get_product_terms( $product->get_id(), 'pa_talla', [ 'fields' => 'names' ] );

    if ( ! empty( $size_terms ) ) {
        $tallas = implode( ', ', $size_terms );
        echo '<div class="prs-product-size"><span>Talla:</span> ' . esc_html( $tallas ) . '</div>';
    }
}
add_action( 'woocommerce_single_product_summary', 'prs_show_product_size', 6 );

// Descripción larga en el resumen, como texto simple
function prs_product_long_description() {
    global $post;

    if ( ! $post ) {
        return;
    }

    $content = apply_filters( 'the_content', $post->post_content );

    if ( ! $content ) {
        return;
    }

    echo '<div class="prs-product-long-description">' . $content . '</div>';
}
// La ponemos justo después de talla, antes de precio
add_action( 'woocommerce_single_product_summary', 'prs_product_long_description', 7 );


// ------------ GALERÍA PERSONALIZADA ------------ //

// Sustituir galería nativa por la nuestra
function prs_override_wc_gallery() {
    // Elimina la galería por defecto de WooCommerce
    remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20 );
    // Añade nuestra galería en el mismo hook
    add_action( 'woocommerce_before_single_product_summary', 'prs_custom_product_gallery', 20 );
}
add_action( 'init', 'prs_override_wc_gallery' );

// Galería centrada, sin miniaturas, con flechas y loop
function prs_custom_product_gallery() {
    if ( ! is_product() ) return;

    global $product;
    if ( ! $product ) return;

    $image_ids = [];
    $featured = $product->get_image_id();
    if ( $featured ) $image_ids[] = $featured;

    $gallery = $product->get_gallery_image_ids();
    if ( $gallery ) $image_ids = array_merge($image_ids, $gallery);

    echo '<div class="prs-product-gallery-wrapper">';
    echo '<div class="prs-product-gallery" data-total="'.count($image_ids).'">';

    foreach ($image_ids as $i => $id) {
        echo '<div class="prs-product-slide '.($i === 0 ? 'is-active' : '').'" data-index="'.$i.'">';
        echo wp_get_attachment_image($id, 'large');
        echo '</div>';
    }

    if (count($image_ids) > 1) {
        echo '<button class="prs-product-nav prs-prev">&lsaquo;</button>';
        echo '<button class="prs-product-nav prs-next">&rsaquo;</button>';
    }

    echo '</div>';
    echo '</div>';
}

// Manejar solicitudes AJAX para filtrar productos por categoría
function prs_filter_products_by_category() {
    // Verificar nonce y permisos
    check_ajax_referer( 'prs_filter_nonce', 'security' );

    $category_slug = isset($_POST['category_slug'])
        ? sanitize_title( wp_unslash( $_POST['category_slug'] ) )
        : '';

    $args = [
        'post_type'      => 'product',
        'posts_per_page' => 60,
        'post_status'    => 'publish',
    ];

    if ( $category_slug ) {
        $args['tax_query'] = [
            [
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => $category_slug,
            ],
        ];
    }


    $loop = new WP_Query( $args );

    if ( $loop->have_posts() ) {
        while ( $loop->have_posts() ) {
            $loop->the_post();
            global $product;
            $thumb_id = get_post_thumbnail_id();
            $img = wp_get_attachment_image_src( $thumb_id, 'large' );
            $img_url = $img ? $img[0] : wc_placeholder_img_src();
            $url = get_permalink();

            echo '<a href="' . esc_url( $url ) . '" class="product-item">';
            echo '<img src="' . esc_url( $img_url ) . '" alt="' . the_title_attribute( [ 'echo' => false ] ) . '">';
            echo '<div class="product-overlay">';
            echo '<span>' . get_the_title() . '</span>';
            echo '</div>';
            echo '</a>';
        }
        wp_reset_postdata();
    } else {
        echo '<p>No hay productos en esta categoría.</p>';
    }

    wp_die();
}
add_action( 'wp_ajax_prs_filter_products', 'prs_filter_products_by_category' );
add_action( 'wp_ajax_nopriv_prs_filter_products', 'prs_filter_products_by_category' );


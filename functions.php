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

    // Material Symbols (icons)
    wp_enqueue_style(
        'palancia-material-symbols',
        'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=menu,shopping_bag,add_shopping_cart,add&display=swap',
        [],
        null
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

        wp_enqueue_script(
            'prs-add-to-cart-feedback',
            get_stylesheet_directory_uri() . '/assets/js/add-to-cart-feedback.js',
            [],
            filemtime( get_stylesheet_directory() . '/assets/js/add-to-cart-feedback.js' ),
            true
        );
    }
}
add_action( 'wp_enqueue_scripts', 'prs_enqueue_assets' );

// Encolar el script de filtrado de productos con AJAX
function prs_enqueue_filter_script() {
    if ( ! is_front_page() && ! is_tax( 'product_cat' ) ) {
        return;
    }

    wp_enqueue_script(
        'prs-filter-products',
        get_stylesheet_directory_uri() . '/assets/js/filter-products.js',
        [],
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
add_action( 'woocommerce_single_product_summary', 'prs_show_product_size', 36 );

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
// La ponemos debajo del precio y el botón de añadir al carrito.
add_action( 'woocommerce_single_product_summary', 'prs_product_long_description', 35 );


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

    $prev_url = '';
    $next_url = '';
    $ordered_ids = prs_get_home_ordered_product_ids();
    $current_id  = $product->get_id();
    $total_ids   = count( $ordered_ids );

    if ( $total_ids > 1 ) {
        $current_index = array_search( $current_id, $ordered_ids, true );
        if ( $current_index !== false ) {
            $prev_index = ( $current_index - 1 + $total_ids ) % $total_ids;
            $next_index = ( $current_index + 1 ) % $total_ids;
            $prev_url   = get_permalink( $ordered_ids[ $prev_index ] );
            $next_url   = get_permalink( $ordered_ids[ $next_index ] );
        }
    }

    $image_ids = [];
    $featured = $product->get_image_id();
    if ( $featured ) $image_ids[] = $featured;

    $gallery = $product->get_gallery_image_ids();
    if ( $gallery ) $image_ids = array_merge($image_ids, $gallery);

    if ( empty( $image_ids ) ) {
        $placeholder_url = wc_placeholder_img_src( 'large' );
        echo '<div class="prs-product-gallery" data-total="1" data-prev-url="' . esc_url( $prev_url ) . '" data-next-url="' . esc_url( $next_url ) . '">';
        echo '<div class="prs-product-slide is-active" data-index="0">';
        echo '<img src="' . esc_url( $placeholder_url ) . '" alt="' . esc_attr( $product->get_name() ) . '">';
        echo '</div></div>';
        return;
    }

    echo '<div class="prs-product-gallery" data-total="' . count( $image_ids ) . '" data-prev-url="' . esc_url( $prev_url ) . '" data-next-url="' . esc_url( $next_url ) . '">';

    foreach ($image_ids as $i => $id) {
        echo '<div class="prs-product-slide '.($i === 0 ? 'is-active' : '').'" data-index="'.$i.'">';
        echo wp_get_attachment_image($id, 'large');
        echo '</div>';
    }

    if (count($image_ids) > 1) {
        echo '<button class="prs-product-nav prs-prev" type="button">&lsaquo;</button>';
        echo '<button class="prs-product-nav prs-next" type="button">&rsaquo;</button>';
    }

    echo '</div>';

    if ( count( $image_ids ) > 1 ) {
        echo '<div class="prs-product-dots" aria-label="Galería de producto">';
        foreach ( $image_ids as $i => $id ) {
            $active = $i === 0 ? ' is-active' : '';
            echo '<button class="prs-product-dot' . $active . '" type="button" aria-label="Ver imagen ' . ( $i + 1 ) . '" data-index="' . $i . '"></button>';
        }
        echo '</div>';
    }
}


// Orden de categorias y renderizado de productos en bloques por categoria y precio
function prs_get_product_category_order() {
    return [ 'palancia-merch', 'chaquetas', 'chalecos', 'sudaderas', 'jerseis', 'tracksuits', 'pantalones', 'camisetas', 'bolsos', 'gafas', 'gorras'];
}

/**
 * Obtiene las categorías de producto filtradas y ordenadas según prioridad.
 * Centraliza la lógica usada en Home, Archivo y Single Product.
 */
function prs_get_sorted_product_categories() {
    // 1. Obtener todas las categorías no vacías
    $product_categories = get_terms( [
        'taxonomy'   => 'product_cat',
        'hide_empty' => true,
    ] );

    if ( is_wp_error( $product_categories ) || empty( $product_categories ) ) {
        return [];
    }

    // 2. Excluir categorías
    $excluded_slugs = [ 'tops', 'bottoms', 'accesorios' ];
    $product_categories = array_filter( $product_categories, function ( $cat ) use ( $excluded_slugs ) {
        return ! in_array( strtolower( $cat->slug ), $excluded_slugs, true );
    } );

    // 3. Ordenar según prioridad
    $priority_order = prs_get_product_category_order(); // Reutilizamos la función que ya tenías

    // Función auxiliar para ordenar objetos según un array de slugs
    $sorted = [];
    $lookup = [];

    // Indexar categorías por slug para búsqueda rápida
    foreach ( $product_categories as $cat ) {
        $lookup[ strtolower( $cat->slug ) ] = $cat;
    }

    // 1. PALANCIA MERCH primero
    if ( isset( $lookup['palancia-merch'] ) ) {
        $sorted[] = $lookup['palancia-merch'];
        unset( $lookup['palancia-merch'] );
    }

    // 2. Asegurar que 'todo' va después (usando la real si existe, o una fake si no)
    if ( isset( $lookup['todo'] ) ) {
        $sorted[] = $lookup['todo'];
        unset( $lookup['todo'] );
    } else {
        $sorted[] = (object) [
            'term_id' => 0,
            'name'    => 'Todo',
            'slug'    => 'todo',
        ];
    }

    // Añadir las prioritarias en orden
    foreach ( $priority_order as $slug ) {
        if ( isset( $lookup[ $slug ] ) ) {
            $sorted[] = $lookup[ $slug ];
            unset( $lookup[ $slug ] );
        }
    }

    // Añadir el resto (si hubiera alguna categoría nueva no listada en prioridad)
    foreach ( $lookup as $cat ) {
        $sorted[] = $cat;
    }

    return $sorted;
}

function prs_get_home_ordered_product_ids() {
    $ids  = [];
    $seen = [];

    $base_args = [
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'meta_key'       => '_price',
        'orderby'        => 'meta_value_num',
        'order'          => 'DESC',
        'fields'         => 'ids',
    ];

    foreach ( prs_get_product_category_order() as $slug ) {
        $term = get_term_by( 'slug', $slug, 'product_cat' );
        if ( ! $term || is_wp_error( $term ) ) {
            continue;
        }

        $args = $base_args;
        $args['tax_query'] = [
            [
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => $slug,
            ],
        ];

        $query = new WP_Query( $args );
        if ( ! empty( $query->posts ) ) {
            foreach ( $query->posts as $post_id ) {
                if ( isset( $seen[ $post_id ] ) ) {
                    continue;
                }
                $seen[ $post_id ] = true;
                $ids[]            = $post_id;
            }
        }
        wp_reset_postdata();
    }

    // Rellenar con el resto de productos en orden de precio
    $query = new WP_Query( $base_args );
    if ( ! empty( $query->posts ) ) {
        foreach ( $query->posts as $post_id ) {
            if ( isset( $seen[ $post_id ] ) ) {
                continue;
            }
            $seen[ $post_id ] = true;
            $ids[]            = $post_id;
        }
    }
    wp_reset_postdata();

    return $ids;
}

function prs_render_product_card( $post_id ) {
    $thumb_id   = get_post_thumbnail_id( $post_id );
    $img        = wp_get_attachment_image_src( $thumb_id, 'large' );
    $img_url    = $img ? $img[0] : wc_placeholder_img_src();
    $url        = get_permalink( $post_id );
    $title_attr = the_title_attribute( [ 'echo' => false, 'post' => $post_id ] );
    $product    = function_exists( 'wc_get_product' ) ? wc_get_product( $post_id ) : false;
    $price_html = $product ? $product->get_price_html() : '';

    $html  = '<a href="' . esc_url( $url ) . '" class="product-item">';
    $html .= '<img src="' . esc_url( $img_url ) . '" alt="' . esc_attr( $title_attr ) . '">';
    $html .= '<div class="product-overlay">';
    $html .= '<span class="product-title">' . esc_html( get_the_title( $post_id ) ) . '</span>';
    if ( $price_html ) {
        $html .= '<span class="product-price">' . wp_kses_post( $price_html ) . '</span>';
    }
    $html .= '</div>';
    $html .= '</a>';

    return $html;
}

function prs_render_products_grid( $category_slug = '', $max_products = 0, $size_slug = '' ) {
    $category_slug = strtolower( $category_slug );
    $html          = '';
    $printed       = 0;
    $seen_ids      = [];
    $limit         = (int) $max_products;
    $is_unlimited  = $limit <= 0;

    $base_args = [
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => $is_unlimited ? -1 : $limit,
        'meta_key'       => '_price',
        'orderby'        => 'meta_value_num',
        'order'          => 'DESC',
    ];

    // Helper para aplicar filtro de talla a los argumentos
    $apply_size_filter = function( $args ) use ( $size_slug ) {
        if ( ! empty( $size_slug ) ) {
            if ( ! isset( $args['tax_query'] ) ) {
                $args['tax_query'] = [];
            }
            if ( count( $args['tax_query'] ) > 0 ) {
                $args['tax_query']['relation'] = 'AND';
            }
            $args['tax_query'][] = [
                'taxonomy' => 'pa_talla',
                'field'    => 'slug',
                'terms'    => $size_slug,
            ];
        }
        return $args;
    };

    // Cuando hay categoria concreta (diferente a TODO), solo ese bloque ordenado por precio
    if ( $category_slug && $category_slug !== 'todo' ) {
        $args              = $base_args;
        $args['tax_query'] = [
            [
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => $category_slug,
            ],
        ];
        $args = $apply_size_filter( $args );

        $loop = new WP_Query( $args );

        if ( $loop->have_posts() ) {
            while ( $loop->have_posts() ) {
                if ( ! $is_unlimited && $printed >= $limit ) {
                    break;
                }
                $loop->the_post();
                $post_id = get_the_ID();

                if ( isset( $seen_ids[ $post_id ] ) ) {
                    continue;
                }

                $seen_ids[ $post_id ] = true;
                $html                .= prs_render_product_card( $post_id );
                $printed++;
            }
            wp_reset_postdata();
        }

        return $html;
    }

    // TODO / sin categoria: recorrer categorias en orden y dentro cada una por precio descendente
    foreach ( prs_get_product_category_order() as $slug ) {
        if ( ! $is_unlimited ) {
            $remaining = $limit - $printed;
            if ( $remaining <= 0 ) {
                break;
            }
        }

        $term = get_term_by( 'slug', $slug, 'product_cat' );
        if ( ! $term || is_wp_error( $term ) ) {
            continue;
        }

        $args = $base_args;
        if ( ! $is_unlimited ) {
            $args['posts_per_page'] = $remaining;
        }
        $args['tax_query']      = [
            [
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => $slug,
            ],
        ];
        $args = $apply_size_filter( $args );

        $loop = new WP_Query( $args );

        if ( $loop->have_posts() ) {
            while ( $loop->have_posts() ) {
                if ( ! $is_unlimited && $printed >= $limit ) {
                    break;
                }
                $loop->the_post();
                $post_id = get_the_ID();

                if ( isset( $seen_ids[ $post_id ] ) ) {
                    continue;
                }

                $seen_ids[ $post_id ] = true;
                $html                .= prs_render_product_card( $post_id );
                $printed++;
            }
            wp_reset_postdata();
        }
    }

    // Si faltan productos (categorias no listadas), rellenar por precio descendente sin perder el orden ya impreso
    if ( $is_unlimited || $printed < $limit ) {
        $args                   = $base_args;
        if ( ! $is_unlimited ) {
            $args['posts_per_page'] = $limit;
        }
        $args = $apply_size_filter( $args );

        $loop = new WP_Query( $args );

        if ( $loop->have_posts() ) {
            while ( $loop->have_posts() ) {
                if ( ! $is_unlimited && $printed >= $limit ) {
                    break;
                }
                $loop->the_post();
                $post_id = get_the_ID();

                if ( isset( $seen_ids[ $post_id ] ) ) {
                    continue;
                }

                $seen_ids[ $post_id ] = true;
                $html                .= prs_render_product_card( $post_id );
                $printed++;
            }
            wp_reset_postdata();
        }
    }

    return $html;
}

// Manejar solicitudes AJAX para filtrar productos por categoría
function prs_filter_products_by_category() {
    // Verificar nonce y permisos
    check_ajax_referer( 'prs_filter_nonce', 'security' );

    // Limpiar TODOS los niveles de buffer para asegurar que no va ningún BOM o espacio
    while ( ob_get_level() > 0 ) {
        ob_end_clean();
    }

    $category_slug = isset($_POST['category_slug'])
        ? sanitize_title( wp_unslash( $_POST['category_slug'] ) )
        : '';

    $size_slug = isset($_POST['size_slug'])
        ? sanitize_title( wp_unslash( $_POST['size_slug'] ) )
        : '';

    // TODO => mostrar todo en orden de categorias y precio
    if ( $category_slug === 'todo' ) {
        $category_slug = '';
    }

    $html = prs_render_products_grid( $category_slug, 0, $size_slug );

    if ( $html ) {
        echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escapado dentro de los helpers
    } else {
        echo '<p>No hay productos en esta categoria.</p>';
    }

    wp_die();
}

add_action( 'wp_ajax_prs_filter_products', 'prs_filter_products_by_category' );
add_action( 'wp_ajax_nopriv_prs_filter_products', 'prs_filter_products_by_category' );
// -------- Checkout ligero y con anti-bot -------- //
function prs_checkout_slim_fields( $fields ) {
    unset( $fields['billing']['billing_company'] );
    unset( $fields['billing']['billing_address_2'] );
    // Notas de pedido (si quieres mantenerlas, elimina esta línea)
    unset( $fields['order']['order_comments'] );
    return $fields;
}
add_filter( 'woocommerce_checkout_fields', 'prs_checkout_slim_fields' );

// Honeypot simple para bots en checkout
function prs_checkout_honeypot_field( $checkout ) {
    echo '<div class="prs-checkout-hp" aria-hidden="true">';
    woocommerce_form_field(
        'prs_hp_field',
        [
            'type'  => 'text',
            'class' => [ 'prs-hp' ],
            'label' => __( 'No rellenar', 'palancia-shop' ),
        ],
        ''
    );
    echo '</div>';
}
add_action( 'woocommerce_after_checkout_billing_form', 'prs_checkout_honeypot_field' );

function prs_checkout_honeypot_validate() {
    if ( ! empty( $_POST['prs_hp_field'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- campo honeypot
        wc_add_notice( __( 'No se ha podido procesar el pedido. Inténtalo de nuevo.', 'palancia-shop' ), 'error' );
    }
}
add_action( 'woocommerce_checkout_process', 'prs_checkout_honeypot_validate' );

// ---------- Carrito custom por hooks (para evitar override) ----------
function prs_render_custom_cart() {
    if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
        return;
    }

    $cart_items = WC()->cart->get_cart();

    if ( WC()->cart->is_empty() ) {
        $zero_price = wc_price( 0 );

        echo '<div class="pal-cart-empty">';
        echo '<div class="pal-cart-empty-title">Order Summary</div>';
        echo '<div class="pal-cart-empty-message">Your cart is empty</div>';
        echo '<div class="pal-cart-summary-list">';
        echo '<div class="pal-cart-summary-row"><span>Subtotal</span><span>' . $zero_price . '</span></div>';
        echo '<div class="pal-cart-summary-row"><span>Taxes</span><span>' . $zero_price . '</span></div>';
        echo '<div class="pal-cart-summary-row is-total"><span>Total</span><span>' . $zero_price . '</span></div>';
        echo '</div>';
        echo '</div>';
        return;
    }

	$terms_page_id  = wc_get_page_id( 'terms' );
	$terms_url      = $terms_page_id > 0 ? get_permalink( $terms_page_id ) : '';
	$requires_terms = ! empty( $terms_url );

	echo '<div class="pal-cart-wrapper">';
	foreach ( $cart_items as $cart_item_key => $cart_item ) {
		$product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
		if ( ! $product || ! $product->exists() || $cart_item['quantity'] <= 0 || ! apply_filters( 'woocommerce_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
			continue;
		}

		$product_id        = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );
		$product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $product->is_visible() ? $product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
		$thumbnail         = apply_filters( 'woocommerce_cart_item_thumbnail', $product->get_image( 'woocommerce_thumbnail' ), $cart_item, $cart_item_key );
		$product_name      = apply_filters( 'woocommerce_cart_item_name', $product->get_name(), $cart_item, $cart_item_key );
		$price_html        = apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $product, $cart_item['quantity'] ), $cart_item, $cart_item_key );
		$item_data         = wc_get_formatted_cart_item_data( $cart_item );
		$remove_link       = apply_filters(
			'woocommerce_cart_item_remove_link',
			sprintf(
				'<a href="%s" class="pal-cart-remove button" aria-label="%s" data-product_id="%s" data-product_sku="%s">%s</a>',
				esc_url( wc_get_cart_remove_url( $cart_item_key ) ),
				esc_attr( sprintf( __( 'Remove %s from cart', 'woocommerce' ), wp_strip_all_tags( $product_name ) ) ),
				esc_attr( $product_id ),
				esc_attr( $product->get_sku() ),
				esc_html__( 'Remove', 'woocommerce' )
			),
			$cart_item_key
		);

		echo '<div class="pal-cart-item" data-cart-item-key="' . esc_attr( $cart_item_key ) . '">';

		echo '<div class="pal-cart-thumb">';
		if ( ! $product_permalink ) {
			echo $thumbnail; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} else {
			printf( '<a href="%s">%s</a>', esc_url( $product_permalink ), $thumbnail ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		echo '</div>';

		echo '<div class="pal-cart-main">';
		echo '<div class="pal-cart-title">';
		if ( ! $product_permalink ) {
			echo wp_kses_post( $product_name );
		} else {
			printf( '<a href="%s">%s</a>', esc_url( $product_permalink ), wp_kses_post( $product_name ) );
		}
		echo '</div>';

		if ( $item_data ) {
			echo '<div class="pal-cart-meta">' . $item_data . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		echo '<div class="pal-cart-price pal-cart-price--mobile">' . $price_html . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</div>'; // pal-cart-main

		echo '<div class="pal-cart-right">';
		echo '<div class="pal-cart-remove-wrap">' . $remove_link . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<div class="pal-cart-price">' . $price_html . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</div>'; // pal-cart-right

		echo '</div>'; // pal-cart-item
	}
	echo '</div>'; // pal-cart-wrapper

    echo '<div class="pal-cart-summary">';
    echo '<div class="pal-cart-subtotal"><span class="label">' . esc_html__( 'Subtotal', 'woocommerce' ) . '</span><span class="amount">';
    wc_cart_totals_subtotal_html();
    echo '</span></div>';

	if ( $requires_terms ) {
		echo '<label class="pal-cart-terms">';
		echo '<input type="checkbox" id="pal-cart-terms">';
		echo '<span>' . esc_html__( 'I agree to the', 'woocommerce' ) . ' <a href="' . esc_url( $terms_url ) . '" target="_blank" rel="noopener">' . esc_html__( 'terms and conditions', 'woocommerce' ) . '</a></span>';
		echo '</label>';
	}

	$checkout_classes = 'button pal-cart-checkout' . ( $requires_terms ? ' is-disabled' : '' );
	$checkout_aria    = $requires_terms ? 'true' : 'false';
	echo '<a href="' . esc_url( wc_get_checkout_url() ) . '" class="' . esc_attr( $checkout_classes ) . '" aria-disabled="' . esc_attr( $checkout_aria ) . '">' . esc_html__( 'Checkout', 'woocommerce' ) . '</a>';
	echo '<div class="pal-cart-note"><span>' . esc_html__( 'Limited to 1 per size / item', 'woocommerce' ) . '</span><span class="note-secondary">' . esc_html__( '* Some exceptions apply', 'woocommerce' ) . '</span></div>';
	echo '</div>'; // pal-cart-summary

	wc_enqueue_js(
		"
		const palTerms = document.getElementById('pal-cart-terms');
		const palCheckout = document.querySelector('.pal-cart-checkout');
		const toggleCheckout = () => {
			if (!palCheckout) return;
			if (!palTerms) {
				palCheckout.classList.remove('is-disabled');
				palCheckout.setAttribute('aria-disabled', 'false');
				return;
			}
			if (palTerms.checked) {
				palCheckout.classList.remove('is-disabled');
				palCheckout.setAttribute('aria-disabled', 'false');
			} else {
				palCheckout.classList.add('is-disabled');
				palCheckout.setAttribute('aria-disabled', 'true');
			}
		};
		if (palTerms && palCheckout) {
			palTerms.addEventListener('change', toggleCheckout);
			toggleCheckout();
			palCheckout.addEventListener('click', (event) => {
				if (palCheckout.classList.contains('is-disabled')) {
					event.preventDefault();
				}
            });
        }
        "
    );
}

// Suprimir aviso de producto eliminado del carrito
add_filter( 'woocommerce_cart_item_removed_message', '__return_empty_string' );

// Usar plantilla de carrito personalizada sin override de WooCommerce
function prs_cart_template_override( $template ) {
	if ( is_cart() ) {
		$custom = get_stylesheet_directory() . '/custom-cart.php';
		if ( file_exists( $custom ) ) {
			return $custom;
		}
	}
	return $template;
}
add_filter( 'template_include', 'prs_cart_template_override' );

// Usar plantilla custom para single product en vez del override de WooCommerce
function prs_single_product_template_override( $template ) {
	if ( is_singular( 'product' ) ) {
		$custom = get_stylesheet_directory() . '/custom-single-product.php';
		if ( file_exists( $custom ) ) {
			return $custom;
		}
	}
	return $template;
}
add_filter( 'template_include', 'prs_single_product_template_override', 11 );

// --------- Reglas: productos 1/1 y sin avisos estándar ---------
// Forzar que todos los productos sean vendidos de forma individual (sin cantidades)
add_filter( 'woocommerce_is_sold_individually', '__return_true', 10, 2 );

// Suprimir mensaje de "añadido al carrito"
add_filter( 'wc_add_to_cart_message_html', '__return_empty_string' );

//Activar Application Passwords
add_filter( 'wp_is_application_passwords_available', '__return_true' );

// ---------- Contact form handler ----------
function prs_handle_contact_form() {
    if ( ! isset( $_POST['prs_contact_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['prs_contact_nonce'] ) ), 'prs_contact_form' ) ) {
        wp_die( 'Invalid request.' );
    }

    $name    = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
    $email   = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
    $message = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';

    $referer = wp_get_referer();
    $redirect_base = $referer ? $referer : home_url( '/contact' );

    if ( empty( $name ) || empty( $message ) || ! is_email( $email ) ) {
        wp_safe_redirect( add_query_arg( 'contact', 'error', $redirect_base ) );
        exit;
    }

    $to      = get_option( 'admin_email' );
    $subject = 'Contacto web: ' . $name;
    $body    = "Nombre: {$name}\nEmail: {$email}\n\n{$message}";
    $headers = [ 'Reply-To: ' . $name . ' <' . $email . '>' ];

    $sent = wp_mail( $to, $subject, $body, $headers );

    wp_safe_redirect( add_query_arg( 'contact', $sent ? 'success' : 'error', $redirect_base ) );
    exit;
}

add_action( 'admin_post_prs_contact', 'prs_handle_contact_form' );
add_action( 'admin_post_nopriv_prs_contact', 'prs_handle_contact_form' );

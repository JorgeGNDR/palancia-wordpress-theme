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

    // Quitar descripciÃ³n corta por defecto (excerpt)
    remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 );

    // Quitar tabs, upsells y relacionados de abajo
    remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );
    remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 );
    remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );

    // Quitar breadcrumbs (Inicio / CategorÃ­a / Producto)
    remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );

    // Quitar sidebar de WooCommerce (Buscar, PÃ¡ginas, Archivos, CategorÃ­as)
    remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );
}
add_action( 'init', 'prs_cleanup_single_summary_hooks' );

// Mostrar talla debajo del tÃ­tulo (atributo pa_talla)
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

// DescripciÃ³n larga en el resumen, como texto simple
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
// La ponemos justo despuÃ©s de talla, antes de precio
add_action( 'woocommerce_single_product_summary', 'prs_product_long_description', 7 );


// ------------ GALERÃA PERSONALIZADA ------------ //

// Sustituir galerÃ­a nativa por la nuestra
function prs_override_wc_gallery() {
    // Elimina la galerÃ­a por defecto de WooCommerce
    remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20 );
    // AÃ±ade nuestra galerÃ­a en el mismo hook
    add_action( 'woocommerce_before_single_product_summary', 'prs_custom_product_gallery', 20 );
}
add_action( 'init', 'prs_override_wc_gallery' );

// GalerÃ­a centrada, sin miniaturas, con flechas y loop
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


// Orden de categorias y renderizado de productos en bloques por categoria y precio
function prs_get_product_category_order() {
    return [ 'chaquetas', 'chalecos', 'sudaderas', 'jerseis', 'tracksuits', 'pantalones', 'camisetas', 'bolsos', 'gafas', 'gorras' ];
}

function prs_render_product_card( $post_id ) {
    $thumb_id   = get_post_thumbnail_id( $post_id );
    $img        = wp_get_attachment_image_src( $thumb_id, 'large' );
    $img_url    = $img ? $img[0] : wc_placeholder_img_src();
    $url        = get_permalink( $post_id );
    $title_attr = the_title_attribute( [ 'echo' => false, 'post' => $post_id ] );
    $price_html = function_exists( 'wc_get_product' ) ? wc_get_product( $post_id )->get_price_html() : '';

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

function prs_render_products_grid( $category_slug = '', $max_products = 60 ) {
    $category_slug = strtolower( $category_slug );
    $html          = '';
    $printed       = 0;
    $seen_ids      = [];

    $base_args = [
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => $max_products,
        'meta_key'       => '_price',
        'orderby'        => 'meta_value_num',
        'order'          => 'DESC',
    ];

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

        $loop = new WP_Query( $args );

        if ( $loop->have_posts() ) {
            while ( $loop->have_posts() && $printed < $max_products ) {
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
        $remaining = $max_products - $printed;
        if ( $remaining <= 0 ) {
            break;
        }

        $term = get_term_by( 'slug', $slug, 'product_cat' );
        if ( ! $term || is_wp_error( $term ) ) {
            continue;
        }

        $args = $base_args;
        $args['posts_per_page'] = $remaining;
        $args['tax_query']      = [
            [
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => $slug,
            ],
        ];

        $loop = new WP_Query( $args );

        if ( $loop->have_posts() ) {
            while ( $loop->have_posts() && $printed < $max_products ) {
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
    if ( $printed < $max_products ) {
        $args                   = $base_args;
        $args['posts_per_page'] = $max_products;

        $loop = new WP_Query( $args );

        if ( $loop->have_posts() ) {
            while ( $loop->have_posts() && $printed < $max_products ) {
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

// Manejar solicitudes AJAX para filtrar productos por categorÃ­a
function prs_filter_products_by_category() {
    // Verificar nonce y permisos
    check_ajax_referer( 'prs_filter_nonce', 'security' );

    $category_slug = isset($_POST['category_slug'])
        ? sanitize_title( wp_unslash( $_POST['category_slug'] ) )
        : '';

    // TODO => mostrar todo en orden de categorias y precio
    if ( $category_slug === 'todo' ) {
        $category_slug = '';
    }

    $html = prs_render_products_grid( $category_slug );

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
    echo '<div class="prs-checkout-hp" style="position:absolute;left:-9999px;visibility:hidden;">';
    woocommerce_form_field(
        'prs_hp_field',
        [
            'type'  => 'text',
            'class' => [ 'prs-hp' ],
            'label' => __( 'No rellenar', 'palancia-retro-shop' ),
        ],
        ''
    );
    echo '</div>';
}
add_action( 'woocommerce_after_checkout_billing_form', 'prs_checkout_honeypot_field' );

function prs_checkout_honeypot_validate() {
    if ( ! empty( $_POST['prs_hp_field'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- campo honeypot
        wc_add_notice( __( 'No se ha podido procesar el pedido. Intentalo de nuevo.', 'palancia-retro-shop' ), 'error' );
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
        wc_get_template( 'cart/cart-empty.php' );
        return;
    }

	$terms_page_id = wc_get_page_id( 'terms' );
	$terms_url     = $terms_page_id > 0 ? get_permalink( $terms_page_id ) : '';

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

	if ( $terms_url ) {
		echo '<label class="pal-cart-terms">';
		echo '<input type="checkbox" id="pal-cart-terms">';
		echo '<span>' . esc_html__( 'I agree to the', 'woocommerce' ) . ' <a href="' . esc_url( $terms_url ) . '" target="_blank" rel="noopener">' . esc_html__( 'terms and conditions', 'woocommerce' ) . '</a></span>';
		echo '</label>';
	}

	echo '<a href="' . esc_url( wc_get_checkout_url() ) . '" class="button pal-cart-checkout is-disabled" aria-disabled="true">' . esc_html__( 'Checkout', 'woocommerce' ) . '</a>';
	echo '<div class="pal-cart-note"><span>' . esc_html__( 'Limited to 1 per size / item', 'woocommerce' ) . '</span><span class="note-secondary">' . esc_html__( '* Some exceptions apply', 'woocommerce' ) . '</span></div>';
	echo '</div>'; // pal-cart-summary

	wc_enqueue_js(
		"
		const palTerms = document.getElementById('pal-cart-terms');
		const palCheckout = document.querySelector('.pal-cart-checkout');
		const toggleCheckout = () => {
			if (!palTerms || !palCheckout) return;
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

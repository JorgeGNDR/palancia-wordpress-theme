<?php
defined( 'ABSPATH' ) || exit;

function prs_render_custom_cart() {
    if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
        return;
    }

    $cart_items = WC()->cart->get_cart();

    if ( WC()->cart->is_empty() ) {
        $shop_url = home_url( '/' );

        echo '<div class="pal-cart-empty">';
        echo '<div class="pal-cart-empty-index" aria-hidden="true">00</div>';
        echo '<div class="pal-cart-empty-title">' . esc_html__( 'Tu carrito está vacío', 'palancia-shop' ) . '</div>';
        echo '<div class="pal-cart-empty-message">' . esc_html__( 'Todavía no has añadido ningún producto.', 'palancia-shop' ) . '</div>';
        echo '<a class="pal-cart-empty-action" href="' . esc_url( $shop_url ) . '">' . esc_html__( 'Ver productos', 'palancia-shop' ) . '</a>';
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
				esc_attr( sprintf( __( 'Eliminar %s del carrito', 'palancia-shop' ), wp_strip_all_tags( $product_name ) ) ),
				esc_attr( $product_id ),
				esc_attr( $product->get_sku() ),
				esc_html__( 'Eliminar', 'palancia-shop' )
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
		echo '<span>' . esc_html__( 'Acepto los', 'palancia-shop' ) . ' <a href="' . esc_url( $terms_url ) . '" target="_blank" rel="noopener">' . esc_html__( 'términos y condiciones', 'palancia-shop' ) . '</a></span>';
		echo '</label>';
	}

	$checkout_classes = 'button pal-cart-checkout' . ( $requires_terms ? ' is-disabled' : '' );
	$checkout_aria    = $requires_terms ? 'true' : 'false';
	echo '<a href="' . esc_url( wc_get_checkout_url() ) . '" class="' . esc_attr( $checkout_classes ) . '" aria-disabled="' . esc_attr( $checkout_aria ) . '">' . esc_html__( 'Finalizar compra', 'palancia-shop' ) . '</a>';
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
		$custom = get_theme_file_path( 'custom-cart.php' );
		if ( file_exists( $custom ) ) {
			return $custom;
		}
	}
	return $template;
}
add_filter( 'template_include', 'prs_cart_template_override' );

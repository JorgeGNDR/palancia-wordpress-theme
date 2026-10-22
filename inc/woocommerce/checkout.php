<?php
defined( 'ABSPATH' ) || exit;

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

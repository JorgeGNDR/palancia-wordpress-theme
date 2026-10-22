<?php
defined( 'ABSPATH' ) || exit;

function prs_handle_contact_form() {
    if ( ! isset( $_POST['prs_contact_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['prs_contact_nonce'] ) ), 'prs_contact_form' ) ) {
        wp_die( 'Invalid request.' );
    }

    $name         = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
    $email        = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
    $subject_key  = isset( $_POST['subject'] ) ? sanitize_key( wp_unslash( $_POST['subject'] ) ) : 'other';
    $order_number = isset( $_POST['order_number'] ) ? sanitize_text_field( wp_unslash( $_POST['order_number'] ) ) : '';
    $message      = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';
    $website      = isset( $_POST['website'] ) ? sanitize_text_field( wp_unslash( $_POST['website'] ) ) : '';

    $subjects = [
        'product'  => 'Producto',
        'order'    => 'Pedido',
        'shipping' => 'Envío',
        'return'   => 'Devolución',
        'other'    => 'Otro',
    ];

    $referer = wp_get_referer();
    $redirect_base = remove_query_arg( 'contact', $referer ? $referer : home_url( '/contact' ) );

    if ( $website ) {
        wp_safe_redirect( add_query_arg( 'contact', 'success', $redirect_base ) );
        exit;
    }

    if ( empty( $name ) || empty( $message ) || ! is_email( $email ) || ! isset( $subjects[ $subject_key ] ) ) {
        wp_safe_redirect( add_query_arg( 'contact', 'error', $redirect_base ) );
        exit;
    }

    $to            = get_option( 'admin_email' );
    $subject_label = $subjects[ $subject_key ];
    $subject       = '[PALANCIA] ' . $subject_label . ' / ' . $name;
    $body          = "Nombre: {$name}\nEmail: {$email}\nAsunto: {$subject_label}\nPedido: " . ( $order_number ?: '-' ) . "\n\n{$message}";
    $headers = [ 'Reply-To: ' . $name . ' <' . $email . '>' ];

    $sent = wp_mail( $to, $subject, $body, $headers );

    wp_safe_redirect( add_query_arg( 'contact', $sent ? 'success' : 'error', $redirect_base ) );
    exit;
}

add_action( 'admin_post_prs_contact', 'prs_handle_contact_form' );
add_action( 'admin_post_nopriv_prs_contact', 'prs_handle_contact_form' );

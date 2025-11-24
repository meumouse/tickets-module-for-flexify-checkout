<?php

namespace MeuMouse\Flexify_Checkout\Tickets\Admin;

use MeuMouse\Flexify_Checkout\Tickets\Core\Helpers;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Manage order class
 *
 * @since 1.2.1
 * @package MeuMouse.com
 */
class Order {

    /**
     * Constructor
     * 
     * @since 1.2.1
     */
    public function __construct() {
        // display ticket fields on order details
        add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'display_fields_on_order_details' ), 10, 1 );
    }


    /**
     * Display ticket fields on admin order details
     * 
     * @since 1.0.0
     * @version 1.2.1
     * @param object $order | Order object
     * @return void
     */
    public function display_fields_on_order_details( $order ) {
        if ( 0 === Helpers::ticket_count( $order ) ) {
            return;
        }

        echo '<h3>' . esc_html__( 'Informações dos ingressos', 'tickets-module-for-flexify-checkout' ) . '</h3>';

        for ( $i = 1; $i <= Helpers::ticket_count( $order ); $i++ ) {
            $first_name = get_post_meta( $order->get_id(), 'billing_first_name_' . $i, true );
            $last_name = get_post_meta( $order->get_id(), 'billing_last_name_' . $i, true );
            $cpf = get_post_meta( $order->get_id(), 'billing_cpf_' . $i, true );
            $phone = get_post_meta( $order->get_id(), 'billing_phone_' . $i, true );
            $phone_international = get_post_meta( $order->get_id(), 'billing_phone_' . $i . '_full', true );
            $email = get_post_meta( $order->get_id(), 'billing_email_' . $i, true );

            echo '<p><strong>' . sprintf( esc_html__( 'Ingresso %s', 'tickets-module-for-flexify-checkout' ), $i ) . ':</strong><br>';
            echo esc_html__( 'Nome: ', 'tickets-module-for-flexify-checkout' ) . esc_html( $first_name ) . ' ' . esc_html( $last_name ) . '<br>';
            echo esc_html__( 'CPF: ', 'tickets-module-for-flexify-checkout' ) . esc_html( $cpf ) . '<br>';

            if ( ! empty( $phone_international ) ) {
                echo esc_html__( 'Telefone: ', 'tickets-module-for-flexify-checkout' ) . esc_html( $phone_international ) . '<br>';
            } else {
                if ( ! empty( $phone ) ) {
                    echo esc_html__( 'Telefone: ', 'tickets-module-for-flexify-checkout' ) . esc_html( $phone ) . '<br>';
                }
            }

            echo esc_html__( 'E-mail: ', 'tickets-module-for-flexify-checkout' ) . esc_html( $email ) . '</p>';
        }
    }
}
<?php

namespace MeuMouse\Flexify_Checkout\Tickets\Core;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Add tickets step on locate shipping step
 * 
 * @since 1.0.0
 * @version 1.1.0
 * @package MeuMouse.com
 */
class Checkout {

    /**
     * Construct function
     * 
     * @since 1.0.0
     * @version 1.1.0
     * @return void
     */
    public function __construct() {
        // add tickets step on address fields
        add_filter( 'Flexify_Checkout/Steps/Set_Custom_Steps', array( __CLASS__, 'add_tickets_step' ), 20, 1 );

        // validadate errors
        add_filter( 'flexify_checkout_target_fields_for_check_errors', array( __CLASS__, 'check_ticket_fields_errors' ), 20, 1 );

        // validate ticket fields
        add_action( 'woocommerce_checkout_process', array( __CLASS__, 'validate_ticket_checkout_fields' ) );

        // remove required shipping fields
        add_filter( 'woocommerce_checkout_fields', array( __CLASS__, 'unset_shipping_fields' ), 170 );

        // save ticket fields
        add_action( 'woocommerce_checkout_update_order_meta', array( __CLASS__, 'save_ticket_checkout_fields' ) );
        
        // display ticket fields on order details
        add_action( 'woocommerce_admin_order_data_after_billing_address', array( __CLASS__, 'display_fields_on_order_details' ), 10, 1 );
    }


    /**
     * Add event tickets step
	 *
	 * @since 1.0.0
	 * @param array $steps | Checkout Fields
	 * @return array
     */
    public static function add_tickets_step( $steps ) {
        if ( ! flexify_checkout_only_virtual() ) {
			return $steps;
		}

        // Adds the ticketing step
        $ticket_step = array(
            'callback' => array( __CLASS__, 'render_ticket_details' ),
            'slug' => 'ticket',
            'title' => __('Ingressos', 'tickets-module-for-flexify-checkout'),
            'post_id' => 0,
        );

        // Reorganizes steps to include ticketing step
        $steps = array_values( $steps ); // Reindex the steps

        // Inserts the step as item 1 of the array, being the second step
        array_splice( $steps, 1, 0, array( $ticket_step ) );

        return $steps;
    }


    /**
     * Get cart or order quantity items for ticket count
     *
     * @since 1.0.0
     * @param WC_Order|null $order Optional. Order object to count tickets from. Default is null.
     * @return int Quantity items count
     */
    public static function ticket_count( $order = null ) {
        $ticket_count = 0;

        if ( is_null( $order ) ) {
            // We are in the context of the cart
            if ( WC()->cart ) {
                foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
                    $ticket_count += $cart_item['quantity'];
                }
            }
        } else {
            // We are in the context of an order
            foreach ( $order->get_items() as $item_id => $item ) {
                $ticket_count += $item->get_quantity();
            }
        }

        return $ticket_count;
    }


    /**
	 * Get the billing address when page has not been defined
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function render_ticket_details() {
		$checkout = WC()->checkout; // get object checkout

        // Add fields according to the number of tickets
        for ( $i = 1; $i <= self::ticket_count(); $i++ ) {
            echo '<h3 class="h2 ticket-step-title flexify-heading">' . sprintf( __('Ingresso %s', 'tickets-module-for-flexify-checkout'), $i ) . '</h3>';
    
            woocommerce_form_field('billing_first_name_' . $i, array(
                'type' => 'text',
                'class' => array('form-row-first'),
                'label' => __('Nome', 'tickets-module-for-flexify-checkout'),
                'required' => true,
            ), $checkout->get_value('billing_first_name_' . $i));
    
            woocommerce_form_field('billing_last_name_' . $i, array(
                'type' => 'text',
                'class' => array('form-row-last'),
                'label' => __('Sobrenome', 'tickets-module-for-flexify-checkout'),
                'required' => true,
            ), $checkout->get_value('billing_last_name_' . $i));
    
            woocommerce_form_field('billing_cpf_' . $i, array(
                'type' => 'text',
                'class' => array(
                    'form-row-first',
                    'validate-cpf-field',
                ),
                'label' => __('CPF', 'tickets-module-for-flexify-checkout'),
                'required' => true,
            ), $checkout->get_value('billing_cpf_' . $i));
    
            woocommerce_form_field('billing_phone_' . $i, array(
                'type' => 'text',
                'class' => array(
                    'form-row-last',
                    'validate-phone-field',
                ),
                'label' => __('Telefone', 'tickets-module-for-flexify-checkout'),
                'required' => true,
            ), $checkout->get_value('billing_phone_' . $i));
    
            woocommerce_form_field('billing_email_' . $i, array(
                'type' => 'email',
                'class' => array(
                    'form-row-wide',
                    'validate-email-field',
                ),
                'label' => __('E-mail', 'tickets-module-for-flexify-checkout'),
                'required' => true,
            ), $checkout->get_value('billing_email_' . $i));
        }
	}


    /**
     * Get ticket fields
     * 
     * @since 1.0.0
     * @return array
     */
    public static function get_ticket_fields() {
        $fields_id = array(
            'billing_first_name_',
            'billing_last_name_',
            'billing_cpf_',
            'billing_phone_',
            'billing_email_',
        );

        $validate_fields = array();

        // Add fields according to the number of tickets
        for ( $i = 1; $i <= self::ticket_count(); $i++ ) {
            foreach ( $fields_id as $field ) {
                $validate_fields[] = $field . $i;
            }
        }

        return $validate_fields;
    }


    /**
     * Add ticket fields to target fields for validate rules
     * 
     * @since 1.0.0
     * @param array $target_fields | Checkout fields to validate
     * @return array
     */
    public static function check_ticket_fields_errors( $target_fields ) {
        // Merge dynamic fields with existing fields
        return array_merge( $target_fields, self::get_ticket_fields() );
    }


    /**
     * Validate ticket checkout fields
     * 
     * @since 1.0.0
     * @return void
     */
    public static function validate_ticket_checkout_fields() {
        for ( $i = 1; $i <= self::ticket_count(); $i++ ) {
            if ( empty( $_POST['billing_first_name_' . $i] ) ) {
                wc_add_notice( __('Por favor, preencha o nome para Ingresso ' . $i), 'error' );
            }

            if ( empty( $_POST['billing_last_name_' . $i])) {
                wc_add_notice( __('Por favor, preencha o sobrenome para Ingresso ' . $i), 'error' );
            }

            if ( empty( $_POST['billing_cpf_' . $i] ) ) {
                wc_add_notice( __('Por favor, preencha o CPF para Ingresso ' . $i), 'error' );
            }

            if ( empty( $_POST['billing_phone_' . $i] ) ) {
                wc_add_notice( __('Por favor, preencha o telefone para Ingresso ' . $i), 'error' );
            }

            if ( empty( $_POST['billing_email_' . $i] ) ) {
                wc_add_notice( __('Por favor, preencha o e-mail para Ingresso ' . $i), 'error' );
            }
        }
    }


    /**
     * Remove required shipping fields
     * 
     * @since 1.0.0
     * @param array $fields | Checkout fields
     * @return array
     */
    public static function unset_shipping_fields( $fields ) {
        $fields['billing']['billing_country']['required'] = false;
        $fields['billing']['billing_postcode']['required'] = false;
        $fields['billing']['billing_address_1']['required'] = false;
        $fields['billing']['billing_address_2']['required'] = false;
        $fields['billing']['billing_city']['required'] = false;
        $fields['billing']['billing_state']['required'] = false;

        return $fields;
    }


    /**
     * Save ticket fields on order
     * 
     * @since 1.0.0
     * @param int $order_id | Order ID
     * @return void
     */
    public static function save_ticket_checkout_fields( $order_id ) {
        for ( $i = 1; $i <= self::ticket_count(); $i++ ) {
            if ( ! empty( $_POST['billing_first_name_' . $i] ) ) {
                update_post_meta( $order_id, 'billing_first_name_' . $i, sanitize_text_field( $_POST['billing_first_name_' . $i] ) );
            }

            if ( ! empty( $_POST['billing_last_name_' . $i] ) ) {
                update_post_meta($order_id, 'billing_last_name_' . $i, sanitize_text_field( $_POST['billing_last_name_' . $i] ) );
            }

            if ( ! empty( $_POST['billing_cpf_' . $i] ) ) {
                update_post_meta( $order_id, 'billing_cpf_' . $i, sanitize_text_field( $_POST['billing_cpf_' . $i] ) );
            }

            if ( ! empty( $_POST['billing_phone_' . $i] ) ) {
                update_post_meta($order_id, 'billing_phone_' . $i, sanitize_text_field( $_POST['billing_phone_' . $i] ) );
            }

            if ( ! empty( $_POST['billing_email_' . $i] ) ) {
                update_post_meta( $order_id, 'billing_email_' . $i, sanitize_text_field( $_POST['billing_email_' . $i] ) );
            }
        }
    }


    /**
     * Display ticket fields on admin order details
     * 
     * @since 1.0.0
     * @param object $order | Order object
     * @return void
     */
    public static function display_fields_on_order_details( $order ) {
        echo '<h3>' . __('Informações dos ingressos', 'tickets-module-for-flexify-checkout') . '</h3>';

        for ( $i = 1; $i <= self::ticket_count( $order ); $i++ ) {
            echo '<p><strong>' . sprintf( __('Ingresso %s', 'tickets-module-for-flexify-checkout'), $i ) . ':</strong><br>';
            echo __('Nome: ', 'tickets-module-for-flexify-checkout') . get_post_meta( $order->get_id(), 'billing_first_name_' . $i, true ) . ' ';
            echo get_post_meta( $order->get_id(), 'billing_last_name_' . $i, true ) . '<br>';
            echo __('CPF: ', 'tickets-module-for-flexify-checkout') . get_post_meta( $order->get_id(), 'billing_cpf_' . $i, true ) . '<br>';
            echo __('Telefone: ', 'tickets-module-for-flexify-checkout') . get_post_meta( $order->get_id(), 'billing_phone_' . $i, true ) . '<br>';
            echo __('E-mail: ', 'tickets-module-for-flexify-checkout') . get_post_meta( $order->get_id(), 'billing_email_' . $i, true ) . '</p>';
        }
    }
}

new Checkout();
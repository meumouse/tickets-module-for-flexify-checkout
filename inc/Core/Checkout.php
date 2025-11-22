<?php

namespace MeuMouse\Flexify_Checkout\Tickets\Core;

use MeuMouse\Flexify_Checkout\Tickets\Admin\Product;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Add tickets step on locate shipping step
 * 
 * @since 1.0.0
 * @version 1.2.0
 * @package MeuMouse.com
 */
class Checkout {

    /**
     * Construct function
     * 
     * @since 1.0.0
     * @version 1.2.0
     * @return void
     */
    public function __construct() {
        // add tickets step on address fields
        add_filter( 'Flexify_Checkout/Steps/Set_Custom_Steps', array( $this, 'add_tickets_step' ), 20, 1 );

        // validadate errors
        add_filter( 'Flexify_Checkout/Checkout/Fields/Target_Fields_For_Check_Errors', array( $this, 'check_ticket_fields_errors' ), 20, 1 );

        // validate ticket fields
        add_action( 'woocommerce_checkout_process', array( $this, 'validate_ticket_checkout_fields' ) );

        // save ticket fields
        add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'save_ticket_checkout_fields' ) );
        
        // display ticket fields on order details
        add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'display_fields_on_order_details' ), 10, 1 );

        // refresh ticket step when order review fragments are requested
        add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'append_ticket_fragment' ), 10, 1 );
    }


    /**
     * Add event tickets step
	 *
	 * @since 1.0.0
     * @version 1.2.0
	 * @param array $steps | Checkout Fields
	 * @return array
     */
    public function add_tickets_step( $steps ) {
        if ( ! self::cart_has_ticket_products() ) {
            return $steps;
        }

        // Adds the ticketing step
        $ticket_step = array(
            'callback' => array( $this, 'render_ticket_details' ),
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
     * @version 1.2.0
     * @param WC_Order|null $order Optional. Order object to count tickets from. Default is null.
     * @return int Quantity items count
     */
    public static function ticket_count( $order = null ) {
        if ( is_null( $order ) ) {
            return self::get_cart_ticket_quantity();
        }

        return self::get_order_ticket_quantity( $order );
    }


    /**
     * Check if there are ticket products in the current cart.
     *
     * @since 1.2.0
     * @return bool
     */
    public static function cart_has_ticket_products() {
        return self::get_cart_ticket_quantity() > 0;
    }


    /**
     * Get ticket quantity from the cart only for products flagged as tickets.
     *
     * @since 1.2.0
     * @return int
     */
    protected static function get_cart_ticket_quantity() {
        $ticket_count = 0;

        if ( WC()->cart ) {
            foreach ( WC()->cart->get_cart() as $cart_item ) {
                $ticket_count += Product::get_item_ticket_quantity( $cart_item );
            }
        }

        return $ticket_count;
    }


    /**
     * Get ticket quantity from an order only for products flagged as tickets.
     *
     * @since 1.2.0
     * @param \WC_Order $order Order instance.
     * @return int
     */
    protected static function get_order_ticket_quantity( $order ) {
        if ( ! $order instanceof \WC_Order ) {
            return 0;
        }

        $ticket_count = 0;

        foreach ( $order->get_items() as $item ) {
            $ticket_count += Product::get_item_ticket_quantity( $item );
        }

        return $ticket_count;
    }


    /**
	 * Get the billing address when page has not been defined
	 *
	 * @since 1.2.0
	 * @return void
	 */
    public function render_ticket_details() {
        echo self::get_ticket_fields_markup();
    }


    /**
     * Return rendered ticket fields markup for the current cart/order state.
     *
     * @since 1.0.0
     * @version 1.2.0
     * @return string
     */
    protected static function get_ticket_fields_markup() {
        $ticket_count = self::ticket_count();

        if ( 0 === $ticket_count ) {
            return '';
        }

        // get object checkout
        $checkout = WC()->checkout;

        // start buffer
        ob_start();

        echo '<div id="flexify-ticket-fields" data-ticket-count="' . esc_attr( $ticket_count ) . '">';
            // Add fields according to the number of tickets
            for ( $i = 1; $i <= $ticket_count; $i++ ) {
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
                        'flexify-intl-phone',
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
	    echo '</div>';

        return ob_get_clean();
    }


    /**
     * Get ticket fields
     * 
     * @since 1.0.0
     * @version 1.2.0
     * @return array
     */
    public static function get_ticket_fields() {
        if ( 0 === self::ticket_count() ) {
            return array();
        }

        $fields_id = apply_filters( 'Flexify_Checkout/Tickets/Checkout_Fields', array(
            'billing_first_name_',
            'billing_last_name_',
            'billing_cpf_',
            'billing_phone_',
            'billing_email_',
        ));

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
     * @version 1.2.0
     * @param array $target_fields | Checkout fields to validate
     * @return array
     */
    public function check_ticket_fields_errors( $target_fields ) {
        $ticket_fields = self::get_ticket_fields();

        if ( empty( $ticket_fields ) ) {
            return $target_fields;
        }

        // Merge dynamic fields with existing fields
        return array_merge( $target_fields, $ticket_fields );
    }


    /**
     * Validate ticket checkout fields
     * 
     * @since 1.0.0
     * @version 1.2.0
     * @return void
     */
    public function validate_ticket_checkout_fields() {
        if ( 0 === self::ticket_count() ) {
            return;
        }

        $cpf_list = array();
        $phone_list = array();

        for ( $i = 1; $i <= self::ticket_count(); $i++ ) {
            $first_name_key = 'billing_first_name_' . $i;
            $last_name_key = 'billing_last_name_' . $i;
            $cpf_key = 'billing_cpf_' . $i;
            $phone_key = 'billing_phone_' . $i;
            $email_key = 'billing_email_' . $i;

            $ticket_data = array(
                'index'       => $i,
                'first_name'  => isset( $_POST[ $first_name_key ] ) ? wc_clean( wp_unslash( $_POST[ $first_name_key ] ) ) : '',
                'last_name'   => isset( $_POST[ $last_name_key ] ) ? wc_clean( wp_unslash( $_POST[ $last_name_key ] ) ) : '',
                'cpf'         => isset( $_POST[ $cpf_key ] ) ? wc_clean( wp_unslash( $_POST[ $cpf_key ] ) ) : '',
                'phone'       => isset( $_POST[ $phone_key ] ) ? wc_clean( wp_unslash( $_POST[ $phone_key ] ) ) : '',
                'email'       => isset( $_POST[ $email_key ] ) ? wc_clean( wp_unslash( $_POST[ $email_key ] ) ) : '',
            );

            if ( empty( $_POST[ $first_name_key ] ) ) {
                wc_add_notice(
                    sprintf(
                        /* translators: %d: ticket index */
                        __( 'Por favor, preencha o nome para Ingresso %d.', 'tickets-module-for-flexify-checkout' ),
                        $i
                    ),
                    'error'
                );
            }

            if ( empty( $_POST[ $last_name_key ] ) ) {
                wc_add_notice(
                    sprintf(
                        __( 'Por favor, preencha o sobrenome para Ingresso %d.', 'tickets-module-for-flexify-checkout' ),
                        $i
                    ),
                    'error'
                );
            }

            if ( empty( $_POST[ $cpf_key ] ) ) {
                wc_add_notice(
                    sprintf(
                        __( 'Por favor, preencha o CPF para Ingresso %d.', 'tickets-module-for-flexify-checkout' ),
                        $i
                    ),
                    'error'
                );
            } else {
                // normalize CPF (remove all that not numbers)
                $cpf_normalized = preg_replace( '/\D+/', '', $ticket_data['cpf'] );

                /**
                 * Filter the normalized CPF value before unique validation
                 *
                 * @since 1.1.2
                 * @param string $cpf_normalized | Normalized CPF
                 * @param int $index | Ticket index.
                 */
                $cpf_normalized = apply_filters( 'Flexify_Checkout/Tickets/Normalized_Cpf', $cpf_normalized, $i );

                if ( ! empty( $cpf_normalized ) ) {
                    $cpf_list[ $i ] = $cpf_normalized;
                }
            }

            if ( empty( $_POST[ $phone_key ] ) ) {
                wc_add_notice(
                    sprintf(
                        __( 'Por favor, preencha o telefone para Ingresso %d.', 'tickets-module-for-flexify-checkout' ),
                        $i
                    ),
                    'error'
                );
            } else {
                $phone_normalized = preg_replace( '/\D+/', '', $ticket_data['phone'] );

                /**
                 * Filter the normalized phone value before unique validation.
                 *
                 * @since 1.2.0
                 * @param string $phone_normalized Normalized phone.
                 * @param int    $index            Ticket index.
                 */
                $phone_normalized = apply_filters( 'Flexify_Checkout/Tickets/Normalized_Phone', $phone_normalized, $i );

                if ( ! empty( $phone_normalized ) ) {
                    $phone_list[ $i ] = $phone_normalized;
                }
            }

            if ( empty( $_POST[ $email_key ] ) ) {
                wc_add_notice(
                    sprintf(
                        __( 'Por favor, preencha o e-mail para Ingresso %d.', 'tickets-module-for-flexify-checkout' ),
                        $i
                    ),
                    'error'
                );
            }

            /**
             * Allow integrations to react after a ticket data block is validated.
             *
             * @since 1.2.0
             * @param array $ticket_data Sanitized ticket data for the current index.
             */
            do_action( 'Flexify_Checkout/Tickets/After_Validate_Ticket', $ticket_data );
        }

        // validate unique documents (CPFs)
        self::validate_ticket_unique_cpfs( $cpf_list );
        self::validate_ticket_unique_phones( $phone_list );
    }


    /**
     * Save ticket fields on order
     * 
     * @since 1.0.0
     * @version 1.2.0
     * @param int $order_id | Order ID
     * @return void
     */
    public function save_ticket_checkout_fields( $order_id ) {
        if ( 0 === self::ticket_count() ) {
            return;
        }

        $ticket_count = self::ticket_count();

        for ( $i = 1; $i <= $ticket_count; $i++ ) {
            if ( ! empty( $_POST['billing_first_name_' . $i] ) ) {
                update_post_meta( $order_id, 'billing_first_name_' . $i, sanitize_text_field( $_POST['billing_first_name_' . $i] ) );
            }

            if ( ! empty( $_POST['billing_last_name_' . $i] ) ) {
                update_post_meta( $order_id, 'billing_last_name_' . $i, sanitize_text_field( $_POST['billing_last_name_' . $i] ) );
            }

            if ( ! empty( $_POST['billing_cpf_' . $i] ) ) {
                update_post_meta( $order_id, 'billing_cpf_' . $i, sanitize_text_field( $_POST['billing_cpf_' . $i] ) );
            }

            if ( ! empty( $_POST['billing_phone_' . $i] ) ) {
                update_post_meta( $order_id, 'billing_phone_' . $i, sanitize_text_field( $_POST['billing_phone_' . $i] ) );
            }

            // Save intl phone (E.164) from hidden input
            if ( ! empty( $_POST[ 'billing_phone_' . $i . '_full' ] ) ) {
                update_post_meta( $order_id, 'billing_phone_' . $i . '_full', sanitize_text_field( wp_unslash( $_POST[ 'billing_phone_' . $i . '_full' ] ) ) );
            }

            if ( ! empty( $_POST['billing_email_' . $i] ) ) {
                update_post_meta( $order_id, 'billing_email_' . $i, sanitize_text_field( $_POST['billing_email_' . $i] ) );
            }

            $ticket_data = array(
                'index'      => $i,
                'first_name' => isset( $_POST['billing_first_name_' . $i] ) ? sanitize_text_field( wp_unslash( $_POST['billing_first_name_' . $i] ) ) : '',
                'last_name'  => isset( $_POST['billing_last_name_' . $i] ) ? sanitize_text_field( wp_unslash( $_POST['billing_last_name_' . $i] ) ) : '',
                'cpf'        => isset( $_POST['billing_cpf_' . $i] ) ? sanitize_text_field( wp_unslash( $_POST['billing_cpf_' . $i] ) ) : '',
                'phone'      => isset( $_POST['billing_phone_' . $i] ) ? sanitize_text_field( wp_unslash( $_POST['billing_phone_' . $i] ) ) : '',
                'phone_international' => isset( $_POST['billing_phone_' . $i . '_full'] ) ? sanitize_text_field( wp_unslash( $_POST['billing_phone_' . $i . '_full'] ) ) : '',
                'email'      => isset( $_POST['billing_email_' . $i] ) ? sanitize_email( wp_unslash( $_POST['billing_email_' . $i] ) ) : '',
            );

            /**
             * Action fired after saving ticket information on the order.
             *
             * @since 1.2.0
             *
             * @param int   $order_id   Order ID.
             * @param array $ticket_data Ticket data array.
             */
            do_action( 'Flexify_Checkout/Tickets/After_Save_Ticket', $order_id, $ticket_data );
        }
    }


    /**
     * Display ticket fields on admin order details
     * 
     * @since 1.0.0
     * @version 1.2.0
     * @param object $order | Order object
     * @return void
     */
    public function display_fields_on_order_details( $order ) {
        if ( 0 === self::ticket_count( $order ) ) {
            return;
        }

        echo '<h3>' . esc_html__( 'Informações dos ingressos', 'tickets-module-for-flexify-checkout' ) . '</h3>';

        for ( $i = 1; $i <= self::ticket_count( $order ); $i++ ) {
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


    /**
     * Validate that each ticket has a unique CPF.
     *
     * @since 1.1.2
     * @param array $cpf_list List of normalized CPFs indexed by ticket position.
     * @return void
     */
    protected static function validate_ticket_unique_cpfs( $cpf_list ) {
        if ( empty( $cpf_list ) || ! is_array( $cpf_list ) ) {
            return;
        }

        /**
         * Allow duplicate CPFs on tickets.
         *
         * If this filter returns true, CPF uniqueness validation will be skipped.
         *
         * @since 1.1.2
         * @param bool  $allow_duplicates | Default false.
         * @param array $cpf_list | List of CPFs to be validated.
         */
        $allow_duplicates = apply_filters( 'Flexify_Checkout/Tickets/Allow_Duplicate_Cpfs', false, $cpf_list );

        if ( true === $allow_duplicates ) {
            return;
        }

        $seen = array();
        $duplicates = array(); // store ticket index

        foreach ( $cpf_list as $index => $cpf ) {
            if ( '' === $cpf ) {
                continue;
            }

            if ( isset( $seen[ $cpf ] ) ) {
                $duplicates[] = $index;
            } else {
                $seen[ $cpf ] = $index;
            }
        }

        if ( empty( $duplicates ) ) {
            return;
        }

        // add notice for each duplicated ticket
        foreach ( $duplicates as $index ) {
            wc_add_notice(
                sprintf(
                    /* translators: %d: ticket index */
                    __( 'O CPF informado para o Ingresso %d já foi utilizado em outro ingresso. Informe um CPF diferente.', 'tickets-module-for-flexify-checkout' ),
                    $index
                ),
                'error'
            );
        }
    }


    /**
     * Validate that each ticket has a unique phone number.
     *
     * @since 1.2.0
     * @param array $phone_list List of normalized phones indexed by ticket position.
     * @return void
     */
    protected static function validate_ticket_unique_phones( $phone_list ) {
        if ( empty( $phone_list ) || ! is_array( $phone_list ) ) {
            return;
        }

        /**
         * Allow duplicate phones on tickets.
         *
         * If this filter returns true, phone uniqueness validation will be skipped.
         *
         * @since 1.2.0
         * @param bool  $allow_duplicates Default false.
         * @param array $phone_list       List of phones to be validated.
         */
        $allow_duplicates = apply_filters( 'Flexify_Checkout/Tickets/Allow_Duplicate_Phones', false, $phone_list );

        if ( true === $allow_duplicates ) {
            return;
        }

        $seen = array();
        $duplicates = array();

        foreach ( $phone_list as $index => $phone ) {
            if ( '' === $phone ) {
                continue;
            }

            if ( isset( $seen[ $phone ] ) ) {
                $duplicates[] = $index;
            } else {
                $seen[ $phone ] = $index;
            }
        }

        if ( empty( $duplicates ) ) {
            return;
        }

        foreach ( $duplicates as $index ) {
            wc_add_notice(
                sprintf(
                    /* translators: %d: ticket index */
                    __( 'O telefone informado para o Ingresso %d já foi utilizado em outro ingresso. Informe um telefone diferente.', 'tickets-module-for-flexify-checkout' ),
                    $index
                ),
                'error'
            );
        }
    }


    /**
     * Inject ticket fields into WooCommerce checkout fragments so they refresh when the cart updates.
     *
     * @since 1.2.0
     * @param array $fragments | Checkout fragments.
     * @return array
     */
    public function append_ticket_fragment( $fragments ) {
        if ( ! defined('IS_FLEXIFY_CHECKOUT') || ! IS_FLEXIFY_CHECKOUT ) {
            return $fragments;
        }

        $fragments['#flexify-ticket-fields'] = self::get_ticket_fields_markup();

        return $fragments;
    }
}
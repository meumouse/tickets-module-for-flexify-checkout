<?php

namespace MeuMouse\Flexify_Checkout\Tickets\Core;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Register/enqueue frontend and backend scripts
 *
 * @since 1.0.0
 * @version 1.1.0
 * @package MeuMouse.com
 */
class Assets {

    /**
     * Construct function
     * 
     * @since 1.0.0
     * @return void
     */
    public function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'frontend_assets' ) );
    }


    /**
     * Add assets to frontend
     * 
     * @since 1.0.0
     * @version 1.2.0
     * @return void
     */
    public function frontend_assets() {
        // display scrips only checkout page
        if ( ! defined( 'IS_FLEXIFY_CHECKOUT' ) || ! IS_FLEXIFY_CHECKOUT ) {
			return;
		}

        if ( 0 === Checkout::ticket_count() ) {
            return;
        }

        wp_enqueue_script( 'flexify-checkout-ticket-fields', FLEXIFY_CHECKOUT_TICKETS_ASSETS . 'js/tickets-module-for-flexify-checkout-frontend.js', array('jquery'), FLEXIFY_CHECKOUT_TICKETS_VERSION );
        wp_enqueue_style( 'flexify-checkout-tickets-styles', FLEXIFY_CHECKOUT_TICKETS_ASSETS . 'css/flexify-checkout-tickets-styles.css', array(), FLEXIFY_CHECKOUT_TICKETS_VERSION );

        $ticket_fields_params = apply_filters( 'flexify_checkout_ticket_fields', array(
            'fields_to_mask' => Checkout::get_ticket_fields(),
        ));

        wp_localize_script( 'flexify-checkout-ticket-fields', 'fcw_ticket_fields_params', $ticket_fields_params );
    }
}
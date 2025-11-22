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
     * Plugin assets directory
     * 
     * @since 1.2.0
     * @return string
     */
    public $assets_url = FLEXIFY_CHECKOUT_TICKETS_ASSETS;

    /**
     * Plugin version
     * 
     * @since 1.2.0
     * @return string
     */
    public $plugin_version = FLEXIFY_CHECKOUT_TICKETS_VERSION;

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

        wp_enqueue_script( 'flexify-checkout-ticket-scripts', $this->assets_url . 'frontend/js/fct-scripts.js', array('jquery'), $this->plugin_version );
        wp_enqueue_style( 'flexify-checkout-tickets-styles', $this->assets_url . 'frontend/css/fct-styles.css', array(), $this->plugin_version );

        $params = apply_filters( 'Flexify_Checkout/Tickets/Frontend_Scripts', array(
            'fields_to_mask' => Checkout::get_ticket_fields(),
            'i18n' => array(
                
            ),
        ));

        wp_localize_script( 'flexify-checkout-ticket-scripts', 'fcw_ticket_fields_params', $params );
    }
}
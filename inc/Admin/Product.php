<?php

namespace MeuMouse\Flexify_Checkout\Tickets\Admin;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Manage product configuration for ticket flow.
 *
 * @since 1.2.0
 * @package MeuMouse.com
 */
class Product {
    /**
     * Meta key that flags a product as a ticket.
     * 
     * @since 1.2.0
     * @return string
     */
    public const META_KEY = '_flexify_checkout_ticket_product';

    /**
     * Register hooks.
     * 
     * @since 1.2.0
     * @return void
     */
    public function __construct() {
        add_action( 'woocommerce_product_options_general_product_data', array( $this, 'render_ticket_checkbox' ) );
        add_action( 'woocommerce_admin_process_product_object', array( $this, 'save_ticket_checkbox' ) );
    }


    /**
     * Display the checkbox field inside the product data panel.
     *
     * @since 1.2.0
     * @return void
     */
    public function render_ticket_checkbox() {
        echo '<div class="options_group">';

        woocommerce_wp_checkbox(
            array(
                'id' => self::META_KEY,
                'label' => __( 'Produto de ingresso', 'tickets-module-for-flexify-checkout' ),
                'description' => __( 'Permite coletar dados dos participante do ingresso durante a finalização da compra.', 'tickets-module-for-flexify-checkout' ),
            )
        );

        echo '</div>';
    }


    /**
     * Save checkbox value when the product is stored.
     *
     * @since 1.2.0
     * @param \WC_Product $product Product instance being saved.
     * @return void
     */
    public function save_ticket_checkbox( $product ) {
        $is_ticket = isset( $_POST[ self::META_KEY ] ) ? 'yes' : 'no';

        $product->update_meta_data( self::META_KEY, $is_ticket );
    }


    /**
     * Determine if a product should trigger the ticket step.
     *
     * @since 1.2.0
     * @param \WC_Product|int|null $product Product instance or ID.
     * @return bool
     */
    public static function is_ticket_product( $product ) {
        $product = self::get_product_instance( $product );

        if ( ! $product ) {
            return false;
        }

        $is_ticket = $product->get_meta( self::META_KEY );

        if ( '' === $is_ticket && $product->is_type( 'variation' ) ) {
            $parent_id = $product->get_parent_id();
            $is_ticket = $parent_id ? get_post_meta( $parent_id, self::META_KEY, true ) : '';
        }

        $is_ticket = 'yes' === $is_ticket;

        /**
         * Filter the product ticket flag.
         *
         * @since 1.2.0
         *
         * @param bool        $is_ticket Flag result.
         * @param \WC_Product $product   Product instance.
         */
        return (bool) apply_filters( 'Flexify_Checkout/Tickets/Is_Ticket_Product', $is_ticket, $product );
    }


    /**
     * Get the ticket quantity represented by a cart or order item.
     *
     * @since 1.2.0
     * @param array|\WC_Order_Item $item Cart item array or order item object.
     * @return int
     */
    public static function get_item_ticket_quantity( $item ) {
        $product = null;
        $quantity = 0;

        if ( is_array( $item ) ) {
            $product = isset( $item['data'] ) ? $item['data'] : null;
            $quantity = isset( $item['quantity'] ) ? (int) $item['quantity'] : 0;
        } elseif ( is_object( $item ) && method_exists( $item, 'get_product' ) ) {
            $product = $item->get_product();
            $quantity = (int) ( method_exists( $item, 'get_quantity' ) ? $item->get_quantity() : 0 );
        }

        if ( ! self::is_ticket_product( $product ) ) {
            return 0;
        }

        return $quantity;
    }


    /**
     * Load a product instance from different identifiers.
     *
     * @since 1.2.0
     * @param \WC_Product|int|null $product Product instance or ID.
     * @return \WC_Product|null
     */
    protected static function get_product_instance( $product ) {
        if ( $product instanceof \WC_Product ) {
            return $product;
        }

        if ( $product ) {
            return wc_get_product( $product );
        }

        return null;
    }
}
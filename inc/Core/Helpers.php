<?php

namespace MeuMouse\Flexify_Checkout\Tickets\Core;

use MeuMouse\Flexify_Checkout\Tickets\Admin\Product;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Helpers class
 *
 * @since 1.2.1
 * @package MeuMouse.com
 */
class Helpers {

    /**
     * Get cart or order quantity items for ticket count
     *
     * @since 1.0.0
     * @version 1.2.1
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
     * @version 1.2.1
     * @return bool
     */
    public static function cart_has_ticket_products() {
        return self::get_cart_ticket_quantity() > 0;
    }


    /**
     * Get ticket quantity from the cart only for products flagged as tickets.
     *
     * @since 1.2.0
     * @version 1.2.1
     * @return int
     */
    public static function get_cart_ticket_quantity() {
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
     * @version 1.2.1
     * @param \WC_Order $order Order instance.
     * @return int
     */
    public static function get_order_ticket_quantity( $order ) {
        if ( ! $order instanceof \WC_Order ) {
            return 0;
        }

        $ticket_count = 0;

        foreach ( $order->get_items() as $item ) {
            $ticket_count += Product::get_item_ticket_quantity( $item );
        }

        return $ticket_count;
    }
}
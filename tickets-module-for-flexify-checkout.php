<?php

/**
 * Plugin Name: 			Flexify Checkout: Módulo de Ingressos
 * Description: 			Extensão que adiciona detalhes de ingressos, exclusivo para o Flexify Checkout para WooCommerce.
 * Plugin URI: 				https://meumouse.com/plugins/flexify-checkout-para-woocommerce/
 * Author: 					MeuMouse.com
 * Author URI: 				https://meumouse.com/
 * Version: 				1.1.0
 * WC requires at least: 	6.0.0
 * WC tested up to: 		10.3.5
 * Requires PHP: 			7.4
 * Tested up to:      		6.8.3
 * Text Domain: 			tickets-module-for-flexify-checkout
 * Domain Path: 			/languages
 * License: 				GPL2
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Flexify_Checkout_Tickets
 * 
 * @since 1.0.0
 * @version 1.1.0
 * @package MeuMouse.com
 */
class Flexify_Checkout_Tickets {

	/**
	 * Plugin slug.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public static $slug = 'tickets-module-for-flexify-checkout';

	/**
	 * Plugin version.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public static $version = '1.1.0';


	/**
	 * Construct the plugin
	 * 
	 * @since 1.0.0
	 * @version 1.1.0
	 * @return void
	 */
	public function __construct() {
        $this->define_constants();

        add_action( 'before_woocommerce_init', array( __CLASS__, 'setup_hpos_compatibility' ) );
	}

    
    /**
	 * Define constants
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	private function define_constants() {
		$this->define( 'FLEXIFY_CHECKOUT_TICKETS_FILE', __FILE__ );
		$this->define( 'FLEXIFY_CHECKOUT_TICKETS_PATH', plugin_dir_path( __FILE__ ) );
		$this->define( 'FLEXIFY_CHECKOUT_TICKETS_URL', plugin_dir_url( __FILE__ ) );
		$this->define( 'FLEXIFY_CHECKOUT_TICKETS_ASSETS', FLEXIFY_CHECKOUT_TICKETS_URL . 'assets/' );
		$this->define( 'FLEXIFY_CHECKOUT_TICKETS_INC_PATH', FLEXIFY_CHECKOUT_TICKETS_PATH . 'inc/' );
		$this->define( 'FLEXIFY_CHECKOUT_TICKETS_BASENAME', plugin_basename( __FILE__ ) );
		$this->define( 'FLEXIFY_CHECKOUT_TICKETS_VERSION', self::$version );
		$this->define( 'FLEXIFY_CHECKOUT_TICKETS_SLUG', self::$slug );
	}


    /**
	 * Define constant if not already set
	 *
	 * @since 1.0.0
	 * @param string $name | Constant name
	 * @param string|bool $value | Constant value
	 * @return void
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}


    /**
	 * Setp compatibility with HPOS/Custom order table feature of WooCommerce.
	 *
	 * @since 1.0.0
	 * @version 1.1.0
	 * @return void
	 */
	public static function setup_hpos_compatibility() {
		if ( class_exists( FeaturesUtil::class ) ) {
			FeaturesUtil::declare_compatibility( 'custom_order_tables', FLEXIFY_CHECKOUT_TICKETS_FILE, true );
		}
	}
}

new Flexify_Checkout_Tickets();
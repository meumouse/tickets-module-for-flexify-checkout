<?php

/**
 * Plugin Name: 			Módulo adicional de ingressos para Flexify Checkout para WooCommerce
 * Description: 			Extensão que adiciona detalhes de ingressos, exclusivo para o Flexify Checkout para WooCommerce.
 * Plugin URI: 				https://meumouse.com/plugins/flexify-checkout-para-woocommerce/
 * Author: 					MeuMouse.com
 * Author URI: 				https://meumouse.com/
 * Version: 				1.0.0
 * WC requires at least: 	6.0.0
 * WC tested up to: 		9.0.0
 * Requires PHP: 			7.4
 * Tested up to:      		6.5.2
 * Text Domain: 			tickets-module-for-flexify-checkout
 * Domain Path: 			/languages
 * License: 				GPL2
 */

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Flexify_Checkout_Tickets
 */
class Flexify_Checkout_Tickets extends Flexify_Checkout {

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
	public static $version = '1.0.0';


	/**
	 * Construct the plugin
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function __construct() {
        parent::__construct();

        $this->define_constants();

        add_action( 'before_woocommerce_init', array( __CLASS__, 'setup_hpos_compatibility' ) );
        $this->setup_includes();
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
	 * @return void
	 */
	public static function setup_hpos_compatibility() {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', FLEXIFY_CHECKOUT_TICKETS_FILE, true );
		}
	}


	/**
	 * Load classes
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	private function setup_includes() {
        /**
         * Add tickets step changes
         * 
         * @since 1.0.0
         */
        include_once FLEXIFY_CHECKOUT_TICKETS_INC_PATH . 'classes/class-flexify-checkout-tickets-core.php';

        /**
         * Include assets
         * 
         * @since 1.0.0
         */
        include_once FLEXIFY_CHECKOUT_TICKETS_INC_PATH . 'classes/class-flexify-checkout-tickets-assets.php';
	}
}

new Flexify_Checkout_Tickets();
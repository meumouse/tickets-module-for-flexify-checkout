<?php

/**
 * Plugin Name: 			Flexify Checkout: Módulo de Ingressos
 * Description: 			Extensão que adiciona detalhes de ingressos, exclusivo para o Flexify Checkout para WooCommerce.
 * Plugin URI: 				https://meumouse.com/plugins/flexify-checkout-para-woocommerce/
 * Author: 					MeuMouse.com
 * Author URI: 				https://meumouse.com/
 * Version: 				1.1.2
 * WC requires at least: 	6.0.0
 * WC tested up to: 		10.3.5
 * Requires PHP: 			7.4
 * Tested up to:      		6.8.3
 * Text Domain: 			tickets-module-for-flexify-checkout
 * Domain Path: 			/languages
 * License: 				GPL2
 */

namespace MeuMouse\Flexify_Checkout\Tickets;

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
	public static $version = '1.1.2';


	/**
	 * Construct the plugin
	 * 
	 * @since 1.0.0
	 * @version 1.1.0
	 * @return void
	 */
	public function __construct() {
        $this->setup_constants();

        add_action( 'before_woocommerce_init', array( $this, 'setup_hpos_compatibility' ) );

		// load Composer
		require_once FLEXIFY_CHECKOUT_TICKETS_PATH . 'vendor/autoload.php';

		// initialize classes
		new \MeuMouse\Flexify_Checkout\Tickets\Core\Checkout;
		new \MeuMouse\Flexify_Checkout\Tickets\Core\Assets;
	}

    
    /**
	 * Define constants
	 * 
	 * @since 1.0.0
	 * @version 1.1.0
	 * @return void
	 */
	private function setup_constants() {
		$base_file = __FILE__;
		$base_dir = plugin_dir_path( $base_file );
		$base_url = plugin_dir_url( $base_file );

		$constants = array(
			'FLEXIFY_CHECKOUT_TICKETS_BASENAME' => plugin_basename( $base_file ),
			'FLEXIFY_CHECKOUT_TICKETS_FILE' => $base_file,
			'FLEXIFY_CHECKOUT_TICKETS_PATH' => $base_dir,
			'FLEXIFY_CHECKOUT_TICKETS_INC_PATH' => $base_dir . 'inc/',
			'FLEXIFY_CHECKOUT_TICKETS_URL' => $base_url,
			'FLEXIFY_CHECKOUT_TICKETS_ASSETS' => $base_url . 'assets/',
			'FLEXIFY_CHECKOUT_TICKETS_ABSPATH' => dirname( $base_file ) . '/',
			'FLEXIFY_CHECKOUT_TICKETS_SLUG' => self::$slug,
			'FLEXIFY_CHECKOUT_TICKETS_VERSION' => self::$version,
		);

		// iterate for each constant item
		foreach ( $constants as $key => $value ) {
			if ( ! defined( $key ) ) {
				define( $key, $value );
			}
		}
	}


    /**
	 * Setp compatibility with HPOS/Custom order table feature of WooCommerce.
	 *
	 * @since 1.0.0
	 * @version 1.1.0
	 * @return void
	 */
	public function setup_hpos_compatibility() {
		if ( class_exists( FeaturesUtil::class ) ) {
			FeaturesUtil::declare_compatibility( 'custom_order_tables', FLEXIFY_CHECKOUT_TICKETS_FILE, true );
		}
	}
}

new Flexify_Checkout_Tickets();
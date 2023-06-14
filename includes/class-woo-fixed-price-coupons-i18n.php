<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://framework.tech
 * @since      1.0.0
 *
 * @package    Woo_Fixed_Price_Coupons
 * @subpackage Woo_Fixed_Price_Coupons/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Woo_Fixed_Price_Coupons
 * @subpackage Woo_Fixed_Price_Coupons/includes
 * @author     Vladimir Eric <vladimir@framework.tech>
 */
class Woo_Fixed_Price_Coupons_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'woo-fixed-price-coupons',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}

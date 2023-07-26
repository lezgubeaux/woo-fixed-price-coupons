<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://framework.tech
 * @since             1.0.0
 * @package           Woo_Fixed_Price_Coupons
 *
 * @wordpress-plugin
 * Plugin Name:       Woo Fixed-price coupons
 * Plugin URI:        https://woo-fixed-price-coupons.tech
 * Description:       Convert woo coupons' discount to fixed-price purchase. Relies on Aelia Currency Switcher for WooCommerce, including it's Rate Markup.
 * Version:           1.3.8
 * Author:            Vladimir Eric
 * Author URI:        https://framework.tech
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       woo-fixed-price-coupons
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

/**
 * Debugging - If ve_debug mu-plugin is not installed, add function to output debug
 */
if (!function_exists('ve_debug_log')) {
	function ve_debug_log($message, $title = '', $new = false)
	{
		$filename = WP_CONTENT_DIR . '/woo_cprlm_l_debug-' . $title . '.log';

		// empty the log if requested
		if ($new && file_exists($filename)) {
			wp_delete_file($filename);
		}

		error_log("\r\n" . date('m/d/Y h:i:s a', time()) . " v" . CPRLM_LITE_VERSION . "\r\n" .
			$message . "\r\n", 3, $filename);

		return;
	}
}

/**
 * check which required plugins are active
 */
$plugins = apply_filters('active_plugins', get_option('active_plugins'));

// Make sure WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', $plugins)) {
	ve_debug_log("### ERROR ### WooCommerce is not active on this site! Woo Fixed Price Coupon plugin cannot work without WooCommerce!", "error");
	return;
}
// check which currency exchange plugin is active
if (in_array('woocommerce-aelia-currencyswitcher/woocommerce-aelia-currencyswitcher.php', $plugins)) {
	define('CURRENCY_EXCH', 'Aelia');
} else if (in_array('woocommerce-multilingual/wpml-woocommerce.php', $plugins)) {
	define('CURRENCY_EXCH', 'WPML');
} else {
	define('CURRENCY_EXCH', '');
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define('WOO_FIXED_PRICE_COUPONS_VERSION', '1.38');

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-woo-fixed-price-coupons-activator.php
 */
function activate_woo_fixed_price_coupons()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-woo-fixed-price-coupons-activator.php';
	Woo_Fixed_Price_Coupons_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-woo-fixed-price-coupons-deactivator.php
 */
function deactivate_woo_fixed_price_coupons()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-woo-fixed-price-coupons-deactivator.php';
	Woo_Fixed_Price_Coupons_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_woo_fixed_price_coupons');
register_deactivation_hook(__FILE__, 'deactivate_woo_fixed_price_coupons');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-woo-fixed-price-coupons.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_woo_fixed_price_coupons()
{
	// reset logs
	// ve_debug_log("@@@ using gap @@@", "gap_coupon", 1);
	// ve_debug_log("@@@ on curr exchange, regenrate coupon amount @@@", "updated_coupon", 1);

	$plugin = new Woo_Fixed_Price_Coupons();
	$plugin->run();
}
add_action('wp_loaded', 'run_woo_fixed_price_coupons');

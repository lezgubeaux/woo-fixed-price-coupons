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
 * Version:           1.1.0
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
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define('WOO_FIXED_PRICE_COUPONS_VERSION', '1.1.0');

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

	$plugin = new Woo_Fixed_Price_Coupons();
	$plugin->run();
}
add_action('woocommerce_loaded', 'run_woo_fixed_price_coupons');

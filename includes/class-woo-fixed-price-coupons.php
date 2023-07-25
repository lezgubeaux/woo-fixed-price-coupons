<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://framework.tech
 * @since      1.0.0
 *
 * @package    Woo_Fixed_Price_Coupons
 * @subpackage Woo_Fixed_Price_Coupons/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Woo_Fixed_Price_Coupons
 * @subpackage Woo_Fixed_Price_Coupons/includes
 * @author     Vladimir Eric <vladimir@framework.tech>
 */
class Woo_Fixed_Price_Coupons
{

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Woo_Fixed_Price_Coupons_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct()
	{
		if (defined('WOO_FIXED_PRICE_COUPONS_VERSION')) {
			$this->version = WOO_FIXED_PRICE_COUPONS_VERSION;
		} else {
			$this->version = '1.3.7';
		}
		$this->plugin_name = 'woo-fixed-price-coupons';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Woo_Fixed_Price_Coupons_Loader. Orchestrates the hooks of the plugin.
	 * - Woo_Fixed_Price_Coupons_i18n. Defines internationalization functionality.
	 * - Woo_Fixed_Price_Coupons_Admin. Defines all hooks for the admin area.
	 * - Woo_Fixed_Price_Coupons_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies()
	{

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-woo-fixed-price-coupons-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-woo-fixed-price-coupons-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-woo-fixed-price-coupons-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-woo-fixed-price-coupons-public.php';

		/**
		 * The class defines Woo Coupon Meta data (multicurrency values of a coupon).
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-woo-fixed-price-coupons-coupon-meta.php';

		/**
		 * on each currency exchange (per given currency and amount) get the custom gap, and apply it to the amount
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-woo-fixed-price-coupons-exchange-gap.php';

		/**
		 * manage any currency exchange
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-woo-fixed-price-coupons-exchange.php';

		/** 
		 * multicurrency amounts of coupons
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-woo-fixed-price-coupons-multicurrency-amounts.php';

		$this->loader = new Woo_Fixed_Price_Coupons_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Woo_Fixed_Price_Coupons_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale()
	{

		$plugin_i18n = new Woo_Fixed_Price_Coupons_i18n();

		$this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks()
	{

		$plugin_admin = new Woo_Fixed_Price_Coupons_Admin($this->get_plugin_name(), $this->get_version());

		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');

		/**
		 * add exchange rate gap to any product price
		 */
		// Simple, grouped and external products
		$this->loader->add_filter('woocommerce_product_get_price', $plugin_admin, 'add_gap');
		$this->loader->add_filter('woocommerce_product_get_regular_price', $plugin_admin, 'add_gap');
		// taken from https://stackoverflow.com/questions/45806249/change-product-prices-via-a-hook-in-woocommerce-3

		/** 
		 * define metadata for Multicurrency amounts of a coupon - IF Aelia Currency Switcher is not present
		 */
		if (CURRENCY_EXCH == 'WPML') { // is_WPML

			// define metadata for multicurrency
			$this->loader->add_filter('publish_post', $plugin_admin, 'set_multicurrency_metadata');

			// set metaboxes
			$this->loader->add_action('add_meta_boxes', $plugin_admin, 'set_multicurrency_metaboxes');

			// save custom fields data (when the post is saved)
			$this->loader->add_action('save_post', $plugin_admin, 'save_multicurrency_metaboxes');
		}
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks()
	{

		$plugin_public = new Woo_Fixed_Price_Coupons_Public($this->get_plugin_name(), $this->get_version());

		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');

		/**
		 * dev test outputs (on the checkout page) - ONLY for Eric
		 */
		$this->loader->add_action('plugins_loaded', $plugin_public, 'check_if_right_user_logged_in');

		/**
		 * display custom calculated coupon within subtotal
		 */
		$this->loader->add_filter('woocommerce_cart_totals_coupon_html', $plugin_public, 'hide_coupon_value_to_subtotal', 10, 3);

		/**
		 * when a coupon applied, replace coupon with a hidden coupon,
		 * that will ensure the Total - as requested.
		 * This is the CORE FUNCTION of the plugin
		 */
		$this->loader->add_action('woocommerce_applied_coupon', $plugin_public, 'fwt_fixed_coupon');

		/**
		 * do not output "coupon applied" for hidden coupons
		 */
		$this->loader->add_filter('woocommerce_coupon_message', $plugin_public, 'remove_hidd_coupon_applied', 10, 2);

		/**
		 * delete the hidden coupon from DB, once it is applied to Cart
		 */
		$this->loader->add_filter('woocommerce_checkout_order_processed', $plugin_public, 'delete_hidden_coupon', 10, 3);

		/**
		 * delete the hidden coupon from DB, when it is un-applied
		 */
		$this->loader->add_action("woocommerce_removed_coupon", $plugin_public, 'delete_hidden_coupon_by_code');

		//round cart total up to nearest amount
		$this->loader->add_filter('woocommerce_calculated_total', $plugin_public, 'round_total');

		/**
		 * restore the hidden coupon amount by pre-set custom amount, in the current currency
		 */
		// $this->loader->add_action('woocommerce_before_checkout_form', $plugin_public, 'restore_hidd_coup_amount');

		/**
		 * get coupon main-value (in desired currency)
		 */
		// $this->loader->add_action('woocommerce_applied_coupon', $plugin_public, 'get_coupon_current_value', 10, 1);

		/**
		 * display custom calculated coupon within total
		 */
		// $this->loader->add_filter('woocommerce_calculated_total', $plugin_public, 'display_coupon_value_to_total', 10, 2);


		/**
		 * remove coupon amount from the Subtotal
		 * (needed to hide the fact that we will switch the amount to a value that will work in WC calculation - the way it is requested /as a sales price, not coupon/)
		 */
		// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
		// https://stackoverflow.com/questions/65447443/change-remove-link-text-for-woocommerce-coupon-on-checkout-page

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run()
	{
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name()
	{
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Woo_Fixed_Price_Coupons_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader()
	{
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version()
	{
		return $this->version;
	}
}

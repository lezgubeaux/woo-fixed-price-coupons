<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://framework.tech
 * @since      1.0.0
 *
 * @package    Woo_Fixed_Price_Coupons
 * @subpackage Woo_Fixed_Price_Coupons/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Woo_Fixed_Price_Coupons
 * @subpackage Woo_Fixed_Price_Coupons/public
 * @author     Vladimir Eric <vladimir@framework.tech>
 */
class Woo_Fixed_Price_Coupons_Public
{

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Woo_Fixed_Price_Coupons_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Woo_Fixed_Price_Coupons_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/woo-fixed-price-coupons-public.css', array(), $this->version, 'all');
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts()
	{

		/**
		 * An instance of this class should be passed to the run() function
		 * defined in Woo_Fixed_Price_Coupons_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Woo_Fixed_Price_Coupons_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/woo-fixed-price-coupons-public.js', array('jquery'), $this->version, false);
	}

	/**
	 * Coupon with a "main currency" - the calculation.
	 * # If any 'Multicurrency' found (none or one expected) - re-calculate base (Euro) or other currency values - from the main one, at the current exchange rate
	 */
	// find main currency and its value
	// convert to currently chosen currency
	public function get_coupon_current_value($coupon_code)
	{
		// get all details of a coupon by its code
		$coupon_meta = new Woo_Fixed_Price_Coupons_CouponMeta($coupon_code);

		// log
		// $this->test_output("Multicurrency: " . print_r($coupon_meta->meta, true));

		// find_main_currency()
		$main_currency = $coupon_meta->meta;
		ve_debug_log("Main curr: " . print_r($main_currency, true), "fixed_coupon");
		if (empty($main_currency)) {
			// if none - return (the coupon has only the plain base currency (Euro) value)
			return $coupon_meta->get_amount();
		}

		// if current currency == main currency - no change of the amount is needed
		$current_currency = get_woocommerce_currency();
		ve_debug_log("Current_currency: " . $current_currency, "fixed_coupon");
		if ($main_currency[1] === $current_currency) {
			return $main_currency[0];
		}

		// convert_coupon_value to current currency, based on the coupon's main currency amount;
		$value = $this->convert_coupon_value($main_currency[0], $main_currency[1]);

		// update displayed amount on the checkout page

		// not really needed
		return $value;
	}

	// convert the value
	public function convert_coupon_value($value, $currency)
	{
		ve_debug_log("Step 2: the coupon amounts are recalculated by exch. rates", "fixed_coupon");

		// get current currency
		// get rate of $currency in EUR
		// 												amount	from 		to
		$amount = apply_filters('wc_aelia_cs_convert', $value, $currency, 'EUR');

		ve_debug_log("converted to eur: " . $amount, "fixed_coupon");

		// get current woo currency
		$current_currency = get_woocommerce_currency();
		// if EUR, no conversion
		if ($current_currency == 'EUR') {
			return $amount;
		}

		// convert the value
		$amount = apply_filters('wc_aelia_cs_convert', $amount, 'EUR', $current_currency);

		ve_debug_log("converted to " . $amount . " " . $current_currency, "fixed_coupon");

		return $amount;
	}

	/**
	 * various tests (outputting to Checkout page)
	 */

	// only for Eric
	public function check_if_right_user_logged_in()
	{
		$user = wp_get_current_user();
		ve_debug_log("User: " . $user->user_login, "hooks");
		if ($user->user_login == 'vladimir@framework.tech') {
			// if (is_checkout()) {
			// add_action('the_content', array($this, 'list_all_hooks'));
			// }
		}
	}

	public function list_all_hooks($content)
	{
		$content .= ve_list_hooks();

		return $content;
	}

	// output some test content
	public function test_output($content = '')
	{
		if (is_object($content)) {
			$content = '';
		}
		$text = '<h4>Test Output:</h4>' . $content;

		// 

		echo '<div class="alert">' . $text . '</div>';
	}

	/**
	 * get coupon and alter it before it is applied (but is already submitted to Checkout)
	 */
	public function custom_coupon_discount_amount($discount = 0, $discounting_amount = '', $cart_item = '', $single = '', $coupon)
	{

		ve_debug_log("Step 1: intercept calculating discount", "fixed_coupon");

		// Check if the coupon belongs to Aelia
		// # skipped for now (wasnt't really working, and not necessary) #
		/* if (class_exists('Aelia\WC\AeliaCurrencySwitcher') && $coupon->get_meta('aelia_valid_currencies')) {

			// altering the discount amount
			$discount *= 1.1;
		} */

		if (is_object($coupon)) {
			$coupon_code = $coupon->get_code();
		} else {
			$coupon_code = $coupon;
		}
		$discount = $this->get_coupon_current_value($coupon_code);

		ve_debug_log("Coupon to apply  - " . $discount . " xxxxxxxxxxxxxxxxxxxxxxx", "fixed_coupon");

		return $discount;
	}

	/**
	 * display custom calculated coupon within subtotal
	 */
	function display_coupon_value_to_subtotal($coupon_html, $coupon, $discount_amount_html)
	{

		$discount_amount_html = $this->custom_coupon_discount_amount(0, 0, '', '', $coupon);
		$coupon_html = $discount_amount_html . ' <a href="' . esc_url(add_query_arg('remove_coupon', rawurlencode($coupon->get_code()), defined('WOOCOMMERCE_CHECKOUT') ? wc_get_checkout_url() : wc_get_cart_url())) . '" class="woocommerce-remove-coupon" data-coupon="' . esc_attr($coupon->get_code()) . '">' . __('[Remove]', 'woocommerce') . '</a>';

		return $coupon_html;
	}

	/**
	 * display custom calculated coupon within subtotal
	 */
	function display_coupon_value_to_total($total, $cart)
	{
		$coupon = $cart->get_applied_coupons();
		if (count($coupon) > 0) {
			$total = $this->custom_coupon_discount_amount(0, 0, '', '', $coupon[0]);
		}

		return $total;
	}
}

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
		$coupon_meta = new CouponMeta($coupon_code);

		// log
		$this->test_output("Multicurrency: " . print_r($coupon_meta->meta, true));

		// find_main_currency()
		$main_currency = $coupon_meta->meta;
		if (empty($main_currency)) {
			// if none - return (the coupon has only the plain base currency (Euro) value)
			return;
		}

		// if current currency == main currency - no change needed
		$current_currency = get_woocommerce_currency();
		if ($main_currency[1] === $current_currency) {
			return;
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
		// get current currency
		// get rate of $currency
		$rate = apply_filters('wc_aelia_cs_convert', 1, 'USD', 'EUR');

		// convert the value

		return $value;
	}

	/**
	 * various tests (outputting to Checkout page)
	 */

	// only for Eric
	public function check_if_right_user_logged_in()
	{
		$user = wp_get_current_user();
		if ($user->user_login == 'vladimir@framework.tech') {
			add_action('woocommerce_after_checkout_form', array($this, 'test_output'));
		}
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
}

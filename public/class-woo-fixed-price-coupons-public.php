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

	// post id of a hidden coupon 
	// (that should be deleted when Fixed Price Coupon is applied to the Cart)
	private $hidd_coupon_id;

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
	 * ========== the core functionality of the plugin ==========================
	 * when a coupon applied, replace coupon with a hidden coupon,
	 * that will ensure the Total - as requested
	 */
	public function fwt_fixed_coupon($coupon_code)
	{
		// current coupon ==================================================================
		$c = new Woo_Fixed_Price_Coupons_CouponMeta($coupon_code);
		ve_debug_log("received coupon from _applied " . $coupon_code, "hidd_coupon");
		if (!is_object($c)) {

			ve_debug_log("attempt to apply a non-existing coupon " . $coupon_code, "hidd_coupon");
			return;
		}
		if (substr($coupon_code, 0, 7) == 'fwt_ve_') {

			// this is my hidden coupon, already processed. get out!
			return;
		}

		$coupon_id = $c->get_id();

		// clone current coupon to hidden one (that will carry altered ammount) ============
		$new_id = $this->clone_coupon_to_hidden($coupon_id);
		ve_debug_log($new_id . " was created (a cloned coupon) ", "hidd_coupon");

		// alter the amount
		$coupon_amount = $this->get_coupon_current_value($coupon_code);
		ve_debug_log("Step 0 = The current coupon " . $coupon_id . " saved amount (in current currency): " . $coupon_amount, "hidd_coupon");

		$new_code = wc_get_coupon_code_by_id($new_id);
		$hidd_coupon = new Woo_Fixed_Price_Coupons_CouponMeta($new_code);

		$price = WC()->cart->total;
		$new_amount = 0;
		$meta_value = '';

		// if EUR only, alter base amount
		if (!$hidd_coupon->meta[0]) {
			$new_amount = $price - $coupon_amount;
		} else { // if metadata, alter meta amount
			$meta_value = $this->set_coupon_meta($coupon_id, $hidd_coupon->meta[1], $hidd_coupon->meta[0]);
			$hidd_coupon->update_meta_data('seller_id', $meta_value);
		}
		$hidd_coupon->set_amount($new_amount);

		// remove curr coupon discount from the card
		WC()->cart->remove_coupon($coupon_code);
		ve_debug_log("Step 0.1 = The originally saved coupon is de-applied! ", "hidd_coupon");

		// ve_debug_log(print_r($c, true), "hidd_coupon");

		// save hidden coupon (with already altered amount, etc.)
		$hidd_coupon->save();

		// ve_debug_log("Newly generated hidden coupon: " . print_r($meta_value, true), "hidd_coupon");
		// ve_debug_log(print_r($hidd_coupon, true), "hidd_coupon");

		/* 
		// create hidden coupon =======================================
		$coupon = new WC_Coupon();
		$new_code = 'fwt_ve_' . time();
		$coupon->set_code($new_code);
		$coupon->set_description('Hidden coupon. See the total!');
		// General tab ===
		// discount type can be 'fixed_cart', 'percent' or 'fixed_product', defaults to 'fixed_cart'
		$coupon->set_discount_type('fixed_cart');
		// discount 
		$new_amount = WC()->cart->total - $coupon_amount;
		ve_debug_log("amount " . $new_amount, "hidd_coupon");
		$coupon->set_amount($new_amount);
		// allow free shipping
		// $coupon->set_free_shipping(true);
		// coupon expiry date
		// $coupon->set_date_expires('31-12-2322');
		// Usage Restriction
		// minimum spend
		// $coupon->set_minimum_amount(1000);
		// maximum spend
		// $coupon->set_maximum_amount(50000);
		// individual use only
		$coupon->set_individual_use(true);
		// exclude sale items
		// $coupon->set_exclude_sale_items(true);
		// products
		// $coupon->set_product_ids(array(132));
		// exclude products
		// $coupon->set_excluded_product_ids(array(15, 16));
		// categories
		// $coupon->set_product_categories(array(17));
		// exclude categories
		// $coupon->set_excluded_product_categories(array(19, 20));
		// allowed emails
		// $coupon->set_email_restrictions(
		// 	array(
		// 		'no-reply@rudrastyh.com',
		// 		'kate@rudrastyh.com',
		// 	)
		// );
		// Usage limit tab ===
		// usage limit per coupon
		// $coupon->set_usage_limit(100);
		// limit usage to X items
		// $coupon->set_limit_usage_to_x_items(10);
		// usage limit per user
		// $coupon->set_usage_limit_per_user(2);

		// add metadata (as in Aelia)

		$coupon->save();

		$new_id = $coupon->get_id(); */

		// apply the hidden coupon
		if (!WC()->cart->has_discount($new_code)) {
			WC()->cart->apply_coupon($new_code);
		} else {
			return;
		}

		ve_debug_log("Step 0.2 = hidden coupon applied: " . $new_id . " " . $new_code . " " . $new_amount, "hidd_coupon");

		// on success, the id of a hidden coupon is passed to allow coupon deletion
		$this->hidd_coupon_id = $new_id;
		return;
	}

	/**
	 * Coupon with a "main currency" - the calculation.
	 * # If any 'Multicurrency coupon value' found (none or one expected)
	 * # 1. convert to EUR (if EUR is not the main currency for that coupon)
	 * # 2. convert to the final (currently selected) currency - latest exchange rate
	 */
	public function get_coupon_current_value($coupon_code)
	{
		// get all details of a coupon by its code
		$coupon_meta = new Woo_Fixed_Price_Coupons_CouponMeta($coupon_code);
		$current_currency = get_woocommerce_currency();

		// find_main_currency()
		$main_currency = $coupon_meta->meta;
		if (strlen($main_currency[0]) < 2) {
			// if no Multicurrency value - the coupon has only the plain base currency (Euro) value
			$value = $coupon_meta->get_amount();

			if ($current_currency == 'EUR') {
				// if current currency is EUR, too, no conversion needed
				ve_debug_log("Euro-based coupon needs no custom conversion: " . print_r($value, true), "hidd_coupon");

				return $value;
			} else {
				// convert_coupon_value to current currency
				$value = $this->convert_coupon_value($value, 'EUR');

				return $value;
			}
		}
		ve_debug_log("Main curr: " . print_r($main_currency, true), "hidd_coupon");

		// if current currency == main currency - no change of the amount is needed
		ve_debug_log("Current_currency: " . $current_currency, "hidd_coupon");
		if ($main_currency[1] === $current_currency) {
			return $main_currency[0];
		}

		// convert_coupon_value to current currency, based on the coupon's main currency amount;
		$value = $this->convert_coupon_value($main_currency[0], $main_currency[1]);

		return $value;
	}

	// convert the value
	public function convert_coupon_value($value, $currency)
	{
		ve_debug_log("Step 2: the coupon amounts are recalculated by exch. rates", "hidd_coupon");

		// conversion is done in 2 steps: first - to EUR, second - to current_curreny

		// get current woo currency
		$current_currency = get_woocommerce_currency();
		ve_debug_log("Current Woo currency is: " . $current_currency, "hidd_coupon");

		// if EUR, no first conversion
		if ($currency == 'EUR') {

			// do only the second conversion
			// 												amount	from 		to
			$amount = apply_filters('wc_aelia_cs_convert', $value, 'EUR', $current_currency);

			ve_debug_log("Eur coupon converted to " . $amount . " " . $current_currency, "hidd_coupon");

			return $amount;
		}

		// first conversion: to EUR
		$amount = apply_filters('wc_aelia_cs_convert', $value, $currency, 'EUR');
		ve_debug_log("firstly, converted to EUR: " . $amount, "hidd_coupon");

		// if current is EUR, no second conversion
		if ($current_currency == 'EUR') {

			return $amount;
			ve_debug_log("EUR is the current woo currency. Done! xxxxxxxxxxxxxx", "hidd_coupon");
		}

		// second conversion: EUR to current currency
		$amount = apply_filters('wc_aelia_cs_convert', $amount, 'EUR', $current_currency);

		ve_debug_log("second conversion to " . $amount . " " . $current_currency, "hidd_coupon");

		return $amount;
	}

	/**
	 * duplicate coupon
	 */
	public function clone_coupon_to_hidden($coupon_id)
	{
		// Coupon ID of the coupon you want to duplicate
		// $coupon_id = time(); xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx

		// Retrieve the existing coupon data
		$existing_coupon = get_post($coupon_id);

		if (empty($existing_coupon)) {
			// Handle error if the coupon does not exist
			ve_debug_log("ERROR: attempt to duplicate to hidden - of a not existing coupon!", "error_coupon");

			return;
		}

		// Create a new coupon object
		$new_coupon = array(
			'post_title' => 'fwt_ve_' . time(),
			'post_status' => $existing_coupon->post_status,
			'post_excerpt' => $existing_coupon->post_excerpt . ' (hidden)',
			'post_type' => 'shop_coupon',
		);

		// Duplicate the coupon
		$new_coupon_id = wp_insert_post($new_coupon);

		if (is_wp_error($new_coupon_id)) {
			// Handle error duplicating the coupon
			ve_debug_log("ERROR: saving a hidden coupon did not work!", "error_coupon");

			return;
		}

		// Retrieve the coupon meta data
		$coupon_meta = get_post_meta($coupon_id);

		// Update the coupon meta data for the new coupon
		foreach ($coupon_meta as $meta_key => $meta_values) {
			foreach ($meta_values as $meta_value) {
				add_post_meta($new_coupon_id, $meta_key, $meta_value);
			}
		}

		// return the coupon id to alter amount and apply to Cart
		return $new_coupon_id;
	}

	/**
	 * prevents outputting "coupon applied" for hidden coupons. works on Checkout page
	 */
	public function remove_hidd_coupon_applied($msg, $msg_code)
	{
		if (is_checkout() || wp_doing_ajax() || WC()->cart->get_cart_contents_count() > 0) {
			$coupons = WC()->cart->get_applied_coupons();
			// ve_debug_log("coup on apply hook: " . print_r($coupons, true));
			foreach ($coupons as $coupon) {
				if (substr($coupon, 0, 7) == 'fwt_ve_') {
					// do not display woo msg "coupon applied" - for a hidden coupon
					return "";
				}
			}
		}

		return $msg;
	}

	/**
	 * set meta data for multicurrency value (Aelia Currency Switcher and Woo Multicurrency WPML)
	 */
	public function set_coupon_meta($id, $currency, $amount)
	{

		// is_Aelia
		$meta['current_data']['id'] = $id;
		$meta['current_data']['key'] = '_coupon_currency_data';
		$meta['current_data']['value'][$currency]['coupon_amount'] = $amount;

		// else is_WPML

		return $meta;
	}

	/**
	 * delete the hidden coupon that was applied as Fixed Price Coupon
	 * (discount value remains in the order)
	 */
	public function delete_hidden_coupon()
	{

		if ($this->hidd_coupon_id) {
			// delete the hidden coupon
			$id = $this->hidd_coupon_id;

			$res = wp_delete_post($id);
			if (is_wp_error($res)) {
				ve_debug_log("ERROR - failed attempt to delete coupon with id: " . $id, "error_coupon");
			} else {
				ve_debug_log("Hidden coupon deleted after being applied. Id: " . $id, "hidd_coupon");
			}
		}
		return;
	}

	/**
	 * display custom calculated coupon within subtotal
	 */
	public function hide_coupon_value_to_subtotal($coupon_html, $coupon, $discount_amount_html)
	{

		// $discount_amount_html = $this->custom_coupon_discount_amount(0, 0, '', '', $coupon);

		// hide the hidden coupon ammount, as its value is not user-friendly
		$discount_amount_html = 'XXX ';
		$coupon_html = $discount_amount_html . ' <a href="' . esc_url(add_query_arg('remove_coupon', rawurlencode($coupon->get_code()), defined('WOOCOMMERCE_CHECKOUT') ? wc_get_checkout_url() : wc_get_cart_url())) . '" class="woocommerce-remove-coupon" data-coupon="' . esc_attr($coupon->get_code()) . '">' . __('[Remove]', 'woocommerce') . '</a>';

		return $coupon_html;
	}








	/**
	 * various tests (outputting to Checkout page)
	 */

	// only for Eric
	public function check_if_right_user_logged_in()
	{
		$user = wp_get_current_user();
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

		ve_debug_log("Step 1: intercept calculating discount", "hidd_coupon");

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

		ve_debug_log("Coupon to apply  - " . $discount . " xxxxxxxxxxxxxxxxxxxxxxx", "hidd_coupon");

		return $discount;
	}

	/**
	 * display custom calculated coupon within subtotal
	 */
	/* public function display_coupon_value_to_total($total, $cart)
	{
		$coupon = $cart->get_applied_coupons();
		if (count($coupon) > 0) {
			$total = $this->custom_coupon_discount_amount(0, 0, '', '', $coupon[0]);
		}

		return $total;
	} */
}

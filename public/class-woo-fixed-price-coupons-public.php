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
	 * ========== the core functionality of the plugin ==========================
	 * when a coupon applied, replace coupon with a hidden coupon,
	 * that will ensure the Total - as requested
	 */
	public function fwt_fixed_coupon($coupon_code)
	{
		if (substr($coupon_code, 0, 7) == 'fwt_ve_') {
			// this is my hidden coupon, already processed. get out!
			return;
		}

		// current coupon ==================================================================
		$c = new Woo_Fixed_Price_Coupons_CouponMeta($coupon_code);
		ve_debug_log("received coupon to be applied: " . $coupon_code, "hidd_coupon");

		if (!is_object($c)) {

			ve_debug_log("ERRROR !!! Attempt to apply a non-existing coupon " . $coupon_code, "hidd_coupon");
			return;
		}
		ve_debug_log(print_r($c, true), "hidd_coupon_orig", 1);

		// remove curr coupon discount from the card =======================================
		WC()->cart->remove_coupon($coupon_code);
		ve_debug_log("Step 0.1 = The originally saved coupon is de-applied! ", "hidd_coupon");

		$price_curr = WC()->cart->total;
		ve_debug_log("Total after ctandard coupon de-applied: " . $price_curr, "hidd_coupon");
		$coupon_id = $c->get_id();

		// clone current coupon to hidden one (that will carry altered ammount) ============
		$new_id = $this->clone_coupon_to_hidden($coupon_id);

		if ($new_id == 0) {
			// clone did not succeed, as coupon not found!
			return;
		}
		ve_debug_log("Step 0.2 " . $new_id . " was created (a cloned coupon) ", "hidd_coupon");

		// open newly generated hidden coupon as a custom coupon class object
		$new_code = wc_get_coupon_code_by_id($new_id);
		$hidd_coupon = new Woo_Fixed_Price_Coupons_CouponMeta($new_code);
		ve_debug_log(print_r($hidd_coupon, true), "hidd_coupon_hiddclone", 1);

		// _main vars all belong to the coupon main currency values
		// (this is set in MultiCurrency tab, and is a starting point for any coupon amount exchange)

		$coupon_amount = $c->get_amount();
		ve_debug_log("Step 0.3 = The current coupon " . $coupon_id . " had amount (in base current currency, EUR): " . $coupon_amount, "hidd_coupon");

		// alter the hidden coupon amount, 
		// save to EUR and all Multicurrency fields 
		$currency_curr = get_woocommerce_currency();

		// correct the coupon amount and Multicurrency values =================================
		// (according to core idea of the plugin)

		// get cart price in eur
		if ($currency_curr != 'EUR') {
			$price_tmp = $this->exchange($price_curr, $currency_curr, 'EUR');
		} else {
			$price_tmp = $price_curr;
		}

		$meta_values = '';

		ve_debug_log(
			"Hidd has meta or not?! " .
				"   meta[0]: " . print_r($hidd_coupon->meta[0], true) .
				"price in EUR: " . $price_tmp .
				" orig coupon_amount" . $coupon_amount,
			"hidd_coupon"
		);
		if (!$hidd_coupon->meta[0]) {
			// if only EUR value exists

			// correct hidd cupon amount
			$new_amount = $price_tmp - $coupon_amount;
			$hidd_coupon->set_amount($new_amount);

			// Multicurrency - all empty
			$hidd_coupon->update_meta_data('_coupon_currency_data', $meta_values);
		} else {
			// Multicurrency is present in coupon

			// correct main value
			$val_main = $hidd_coupon->meta[1];
			$curr_main = $hidd_coupon->meta[0];

			$price_main = $this->exchange($price_curr, $currency_curr, $curr_main);
			$hidd_coupon->meta[1] = $price_main - $val_main;

			// set all Multicurrency values
			// 										hidd id		main value			main currency
			$meta_values = $this->define_coupon_meta($new_id, $hidd_coupon->meta[1], $hidd_coupon->meta[0]);
			// save Multicurrency values to the coupon
			$hidd_coupon->update_meta_data('_coupon_currency_data', json_encode($meta_values));
			$hidd_coupon->update_meta_data('current_data', 'xxxxxxxxxxxxxxxxxxxxxxxxx');
			ve_debug_log(
				"xxxxxxxxxxxxxxxxxxxxxxxxxxxxx " . print_r($hidd_coupon->get_meta('current_data'), true),
				"coupon_meta"
			);

			// correct hidd coupon base amount
			$new_amount = $this->exchange($val_main, $curr_main, 'EUR');
			$hidd_coupon->set_amount($new_amount);
		}

		// save & apply hidden coupon (with already corrected amounts)
		$hidd_coupon->save();
		ve_debug_log("Hidden coupon is saved! " . $new_code, "hidd_coupon");
		ve_debug_log(print_r($hidd_coupon, true), "hidd_coupon_hidd", 1);

		// apply the hidden coupon
		if (!WC()->cart->has_discount($new_code)) {
			WC()->cart->apply_coupon($new_code);
		} else {
			return;
		}

		ve_debug_log("Step 0.2 = hidden coupon applied: " . $new_id . " " . $new_code . " " . $new_amount, "hidd_coupon");
		return;
	}

	/**
	 * save the hidden coupon, and apply it to the cart
	 */


	/**
	 * Returns coupon value in a current currency,  calculated from "main currency"
	 */
	/* public function get_coupon_current_value($coupon_code)
	{
		// get all details of a coupon by its code
		$coupon = new Woo_Fixed_Price_Coupons_CouponMeta($coupon_code);
		// currently chosen currency
		$current_currency = get_woocommerce_currency();
		ve_debug_log("Current_currency: " . $current_currency, "hidd_coupon");

		// find_main_currency()
		$main_currency = $coupon->meta; // multicurrency of a coupon is defined as meta
		if (strlen($main_currency[0]) < 2) {
			// if no Multicurrency value - the coupon has only the plain base currency (Euro) value
			$value = $coupon->get_amount();

			// if needed, conversion will be done by currency switch plugin
			ve_debug_log("Main currency is EUR: " . print_r($value, true), "hidd_coupon");

			return $value;
		}

		// a coupon with a main value set in multicurrency (not EUR)
		ve_debug_log("Main currency: " . print_r($main_currency, true), "hidd_coupon");

		// if current currency == main currency - no change of the amount is needed
		if ($main_currency[1] === $current_currency) {
			return $main_currency[0];
		}

		// exchange to current currency, 
		// calculated from coupon's main currency amount
		// 									value				from				to
		$value = $this->exchange($main_currency[0], $main_currency[1]);

		return $value;
	} */

	// exchange the value to any currency
	public function exchange($value, $curr_from, $curr_to = '')
	{
		ve_debug_log("Step 2: the coupon amounts are recalculated by exch. rates", "hidd_coupon");

		// exchange is done in two steps: 1) to EUR, 2) to target currency

		if (!$curr_to) {
			$curr_to = get_woocommerce_currency();
		}
		ve_debug_log("Current Woo currency is: " . $curr_to, "hidd_coupon");

		if ($curr_to == 'EUR') {

			// if EUR, no 1) conversion
			// 												amount	from 		to
			$amount = apply_filters('wc_aelia_cs_convert', $value, 'EUR', $curr_to);

			ve_debug_log("Amount exchanged 
				from " . $curr_from . "=" . $value .
				" to " . $curr_to . "=" . $amount, "hidd_coupon");

			return $amount;
		}

		// first conversion: to EUR
		$amount = apply_filters('wc_aelia_cs_convert', $value, $curr_from, 'EUR');
		ve_debug_log("firstly, exchanged to EUR: " . $amount, "hidd_coupon");

		// if current is EUR, no second conversion
		if ($curr_to == 'EUR') {

			return $amount;
			ve_debug_log("EUR is the current woo currency. Done! xxxxxxxxxxxxxx", "hidd_coupon");
		}

		// second conversion: EUR to current currency
		$amount = apply_filters('wc_aelia_cs_convert', $amount, 'EUR', $curr_to);

		ve_debug_log("second conversion to " . $amount . " " . $curr_to, "hidd_coupon");

		return $amount;
	}

	/**
	 * duplicate coupon
	 */
	public function clone_coupon_to_hidden($coupon_id)
	{

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

		ve_debug_log("Adding meta from orig to hidden " . $new_coupon_id, "hidd_coupon");
		// Update the coupon meta data for the new coupon
		foreach ($coupon_meta as $meta_key => $meta_values) {
			foreach ($meta_values as $meta_value) {
				$output = unserialize($meta_value);
				add_post_meta($new_coupon_id, $meta_key, $output);

				ve_debug_log("### " . $new_coupon_id . " " . $meta_key . " " . $output, "hidd_coupon");
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
	 * define meta data for multicurrency values (Aelia Currency Switcher and Woo Multicurrency WPML)
	 */
	public function define_coupon_meta($id, $amount_main, $currency_main)
	{
		// get all woo currencies
		$currencies = get_woocommerce_currencies();
		ve_debug_log("Active currencies are: " . print_r($currencies, true), "hidd_coupon");

		// which currency switch plugin is active
		if (CURRENCY_EXCH == 'woocommerce-aelia-currencyswitcher') { // is_aliea

			$meta['current_data']['id'] = $id;
			$meta['current_data']['key'] = '_coupon_currency_data';

			foreach ($currencies as $currency) {
				if ($currency == $currency_main) {
					$meta['current_data']['value'][$currency]['coupon_amount'] = $amount_main;
				} else {
					$amount = $this->exchange($amount_main, $currency_main, $currency);
					$meta['current_data']['value'][$currency]['coupon_amount'] = $amount;
				}
			}
		} else { // is_WPML

		}

		// returns complete array that defines all Multicurrency values of a coupon
		return $meta;
	}

	/**
	 * delete the hidden coupon that was applied as Fixed Price Coupon
	 * (discount value remains in the order)
	 */
	public function delete_hidden_coupon($order_id, $posted_data, $order)
	{
		ve_debug_log("Order id: ", $order);
		foreach ($order->get_coupon_codes() as $coupon_code) {

			ve_debug_log("order coupon code: " . $coupon_code, "order");
			if (substr($coupon_code, 0, 7) == 'fwt_ve_') {

				ve_debug_log("Attempting to delete hidden coupon: " . $coupon_code, "hidd_coupon");

				$id = wc_get_coupon_id_by_code($coupon_code);
				$res = ''; // wp_delete_post($id);

				if (is_wp_error($res)) {
					ve_debug_log("ERROR - failed attempt to delete coupon with id: " . $id, "error_coupon");
				} else {
					ve_debug_log("Hidden coupon deleted after being applied. Id: " . $id, "hidd_coupon");
				}
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
}

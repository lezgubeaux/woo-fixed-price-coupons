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
		ve_debug_log("Total after ctandard coupon de-applied: " . $price_curr . " orig am: " . $c->get_amount(), "hidd_coupon");
		$coupon_id = $c->get_id();

		$currency_curr = get_woocommerce_currency();

		// clone current coupon to hidden one (that will carry all ammounts, altered) ============
		$cloned = $this->clone_coupon_to_hidden($c, $coupon_id, $price_curr, $currency_curr);
		$new_code = $cloned[0];
		$new_amount = $cloned[1];
		ve_debug_log("Clone returned: " . print_r($cloned, true), "hidd_coupon");

		if ($new_code === 0) {
			// clone did not succeed, as coupon not found!
			return;
		}
		ve_debug_log("Step 0.2 " . $new_code . " was created (a cloned coupon) ", "hidd_coupon");

		// apply the hidden coupon
		if (!WC()->cart->has_discount($new_code)) {
			WC()->cart->apply_coupon($new_code);
		} else {
			return;
		}

		ve_debug_log("Step 0.2 = hidden coupon applied: " . $new_code . " " . $new_amount, "hidd_coupon");

		return;
	}

	/**
	 * save the hidden coupon, and apply it to the cart
	 */

	// exchange the value to any currency
	public function exchange($value, $curr_from, $curr_to = '')
	{
		ve_debug_log("Step 2: the coupon amounts are recalculated by exch. rates", "hidd_coupon");

		// exchange is done in two steps: 1) to EUR, 2) to target currency

		if (!$curr_to) {
			$curr_to = get_woocommerce_currency();
		}
		ve_debug_log("Current Woo currency is: " . $curr_to, "hidd_coupon");

		$amount = $this->exch_from_to($value, $curr_from, $curr_to);

		return $amount;
	}

	/**
	 * exchange rate
	 */
	public function exch_from_to($amount, $from, $to)
	{
		if (CURRENCY_EXCH == 'woocommerce-aelia-currencyswitcher') {

			if ($to == 'EUR') {

				// if EUR, no 1) conversion
				// 												amount	from 		to
				$res = apply_filters('wc_aelia_cs_convert', $amount, $from, 'EUR');

				ve_debug_log("Amount exchanged 
					from " . $from . "=" . $amount .
					" to " . $to . "=" . $res, "hidd_coupon");

				return $res;
			}

			// first conversion: to EUR
			$res = apply_filters('wc_aelia_cs_convert', $amount, $from, 'EUR');
			ve_debug_log("firstly, exchanged to EUR: " . $amount, "hidd_coupon");

			// second conversion: EUR to current currency
			$res = apply_filters('wc_aelia_cs_convert', $res, 'EUR', $to);
			ve_debug_log("secondly, exchanged to: " . $to . " = " . $amount, "hidd_coupon");

			return $res;
		} else if (CURRENCY_EXCH == 'woocommerce-multilingual') {
			// woocommerce-multilingual manages currency exchange rate
			$wcml = new WCML_Multi_Currency;
			$exch_rates = $wcml->get_exchange_rates();

			ve_debug_log("WCML exch. rates: " . print_r($exch_rates, true), "exch_coupon");
			// apply_filters( 'wcml_exchange_rates', $this->exchange_rates );

			if ($from == 'EUR') {
				// convert from EUR
				return $amount * $exch_rates[$to];
			} else if ($to == 'EUR') {
				// convert to EUR
				return $amount / $exch_rates[$from];
			} else {
				// exchange 2 non-EUR currencies
				$res = $amount / $exch_rates[$from];
				return $res * $exch_rates[$to];
			}
		}
	}

	/**
	 * duplicate coupon
	 */
	// 							orig. coupon	c id		curr cart price	curr currency
	public function clone_coupon_to_hidden($c, $coupon_id, $price_curr, $currency_curr)
	{

		// Retrieve the existing coupon data
		$existing_coupon = get_post($coupon_id);

		if (empty($existing_coupon)) {
			// Handle error if the coupon does not exist
			ve_debug_log("ERROR: attempt to duplicate to hidden - of a not existing coupon!", "error_coupon");

			return;
		}

		// Create a new coupon object
		$new_code = 'fwt_ve_' . time();
		$new_coupon = array(
			'post_title' => $new_code,
			'post_status' => $existing_coupon->post_status,
			'post_excerpt' => $existing_coupon->post_excerpt . ' (hidden)',
			'post_type' => 'shop_coupon',
		);

		// Duplicate the coupon
		$new_coupon_id = wp_insert_post($new_coupon);

		if (is_wp_error($new_coupon_id)) {
			// Handle error duplicating the coupon
			ve_debug_log("ERROR: saving a hidden coupon did not work!", "error_coupon");

			return [0, ''];
		}

		// Retrieve the coupon meta data
		$coupon_meta = get_post_meta($coupon_id);

		// Update the coupon meta data for the new coupon
		ve_debug_log("Adding meta from orig to hidden " . $new_coupon_id . " orig am: " . $c->get_amount(), "hidd_coupon");

		foreach ($coupon_meta as $meta_key => $meta_values) {

			foreach ($meta_values as $meta_value) {

				$vals = $meta_value;
				ve_debug_log("for key: " . $meta_key . " meta_value: " . print_r($vals, true), "hidd_coupon");

				if ($meta_key == '_coupon_currency_data') {

					$vals = unserialize($meta_value);

					if (strlen($c->meta[1]) == 3) {

						// correct main value
						$currency_main = $c->meta[1];

						$amount_main = $c->meta[0];
						// correct the main amount (to get custom calculated discount price)
						if ($currency_curr != $currency_main) {
							// current (cart) currency is not the same as main coupon currency
							$price_main = $this->exchange($price_curr, $currency_curr, $currency_main);
							$amount_main = $price_main - $amount_main;
						} else {
							$amount_main = $price_curr - $amount_main;
						}

						// set all Multicurrency values (corrected!)
						// 									hidd id		main val	main currency 	vals
						$rvals = $this->define_coupon_meta($new_coupon_id, $amount_main, $c->meta[1], $vals);

						ve_debug_log("Meta val from hidd coupon " . print_r($rvals, true), "hidd_coupon");
					} else {

						// Hidd coupon with no custom multicurrency amount set - should not have multicurrency metas processed

						ve_debug_log("NO meta val from hidd coupon - this is EUR-only coupon", "hidd_coupon");
					}
				} else {
					$rvals = $vals;
				}
				// save each meta from original to cloned coupon
				update_post_meta($new_coupon_id, $meta_key, $rvals);
			}
		}

		$hidd_coupon = new Woo_Fixed_Price_Coupons_CouponMeta($new_code);

		if (strlen($c->meta[1]) == 3) {
			$amount_coup = $this->exchange($amount_main, $currency_main, 'EUR');
		} else {
			// if no Multicurrency amount set, use base-currency amount of the coupon

			ve_debug_log("base amount for non-multicurrency coupon: " . $price_curr . " " . $c->get_amount(), "hidd_coupon");
			$amount_main = $price_curr - $c->get_amount();
			$amount_coup = $amount_main;

			if ($currency_curr != 'EUR') {
				$amount_coup = $this->exchange($amount_main, $currency_curr, 'EUR');
			}
		}

		ve_debug_log("Coupon's custom base amount: " . $amount_coup . " orig am: " . $c->get_amount(), "hidd_coupon");
		$hidd_coupon->set_amount($amount_coup);

		// save & apply hidden coupon (with already corrected amounts)
		$hidd_coupon->save();
		ve_debug_log("Hidden coupon was saved! " . $new_code, "hidd_coupon");

		ve_debug_log(print_r($hidd_coupon, true), "hidd_coupon_hidd", 1);

		// return the coupon id with corrected main amount
		$ret = [$new_code, $amount_main];
		return $ret;
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
	 *								hidd id		main value	main currency	val
	 */
	public function define_coupon_meta($id, $amount_main, $currency_main, $vals)
	{
		/** $vals = coupon / meta / _coupon_currency_data / ['value']
		 * Array
		 * 			[USD] => Array
		 * 						['coupon_amount'] = 200
		 * 						...
		 *			<div class=""></div>
		 */

		// which currency switch plugin is active
		if (CURRENCY_EXCH == 'woocommerce-aelia-currencyswitcher') { // is_aliea

			foreach ($vals as $curr_indx => $val) {
				if ($curr_indx == $currency_main) {
					$vals[$curr_indx]['coupon_amount'] = $amount_main;
				} else {
					$amount = $this->exchange($amount_main, $currency_main, $curr_indx);
					$vals[$curr_indx]['coupon_amount'] = $amount;
				}
			}
		} else { // is_WPML

		}

		// returns complete array that defines all Multicurrency values of a coupon
		return $vals;
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
				$res = wp_delete_post($id);

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

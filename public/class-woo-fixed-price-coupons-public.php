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

use WooCommerce\PayPalCommerce\ApiClient\Entity\ExchangeRate;

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

	public $exchange;

	public function __construct($plugin_name, $version)
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		$this->exchange = new Woo_Fixed_Price_Coupons_Exchange;
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
		ve_debug_log("received coupon to be applied: " . $coupon_code, "coup", 1);

		if (!is_object($c)) {

			ve_debug_log("ERRROR !!! Attempt to apply a non-existing coupon " . $coupon_code, "hidd_coupon");
			return;
		}

		ve_debug_log(print_r($c, true), "hidd_coupon_orig", 1);

		// remove curr coupon discount from the card =======================================
		WC()->cart->remove_coupon($coupon_code);
		ve_debug_log("Step 0.1 = The originally saved coupon is de-applied! ", "coup");

		global $woocommerce;
		$price_curr = $woocommerce->cart->total;
		ve_debug_log("Total after ctandard coupon de-applied: " . $price_curr . " amount_main: " . $c->meta[0], "coup");
		$coupon_id = $c->get_id();

		$currency_curr = get_woocommerce_currency();

		// clone current coupon to hidden one (that will carry all ammounts, altered) ============
		// 								orig coup	o coup id	cart amount	 	current currency
		$cloned = $this->clone_coupon_to_hidden($c, $coupon_id, $price_curr, $currency_curr);
		$new_code = $cloned[0];
		$new_amount = $cloned[1];
		ve_debug_log("Clone returned: " . print_r($cloned, true), "coup");

		if ($new_code === 0) {
			// clone did not succeed, as coupon not found!
			return;
		}
		ve_debug_log("Step 3 " . $new_code . " was created (a cloned coupon) ", "coup");

		// apply the hidden coupon
		if (!WC()->cart->has_discount($new_code)) {
			WC()->cart->apply_coupon($new_code);
		} else {
			return;
		}

		ve_debug_log("Step 3.2 = hidden coupon applied - code:" . $new_code . " am:" . $new_amount, "coup");

		return;
	}

	/**
	 * duplicate coupon & alter amounts to match required calculation
	 */
	// 								orig coup	o coup id	o coup am			cart amount		current currency
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
		ve_debug_log("Adding meta from orig to hidden " . $new_coupon_id, "coup");

		foreach ($coupon_meta as $meta_key => $meta_values) {

			foreach ($meta_values as $meta_value) {

				$vals = $meta_value;
				ve_debug_log("for key: " . $meta_key . " meta_value: " . print_r($vals, true), "coup");

				if ($meta_key == '_coupon_currency_data') {

					$vals = unserialize($meta_value);

					// get main value
					$currency_main = $c->meta[1];
					$amount_main = $c->meta[0];
					ve_debug_log("amount_main_orig: " . $amount_main, "coup");

					// preserve the main value

					ve_debug_log("Step 2: \n\r sent to define meta - main amount / currency: " . $amount_main . " / " . $currency_main, "coup");
					// set all Multicurrency values (corrected!)
					// 									hidd id		main val	main currency 		meta vals
					$rvals = $this->define_coupon_meta($currency_curr, $amount_main, $currency_main, $vals);

					ve_debug_log("The coupon amounts are recalculated by exch. rates " . print_r($rvals, true), "coup");
				} else {
					$rvals = $vals;
				}
				// save each meta from original to cloned coupon
				update_post_meta($new_coupon_id, $meta_key, $rvals);
				ve_debug_log("meta updated " . time(), "coup");
			}
		}

		$hidd_coupon = new Woo_Fixed_Price_Coupons_CouponMeta($new_code);

		if (strlen($c->meta[1]) == 3) {
			$amount_coup = $rvals['EUR']['coupon_amount'];
			ve_debug_log("Coupon amount (always in EUR): " . $c->get_amount() . " -> " . $amount_coup, "coup");
		} else {
			// if no Multicurrency amount set, use base-currency amount of the coupon

			if ($currency_curr != $currency_main) {
				$amount_coup = $rvals[$currency_curr]['coupon_amount'];
			} else {
				$amount_coup = $amount_main;
			}
		}
		ve_debug_log("coupon properties - curr price:" . $price_curr . " -> " . $amount_coup, "coup");

		$hidd_coupon->set_amount($amount_coup);

		// save & apply hidden coupon (with already corrected amounts)
		$hidd_coupon->save();
		ve_debug_log("Hidden coupon was saved! " . $new_code . " " . time(), "coup");

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
	public function define_coupon_meta($currency_curr, $amount_main, $currency_main, $vals)
	{
		global $woocommerce;
		$price_curr = WC()->cart->total;
		ve_debug_log("Process current price/currency " . $price_curr . " " . $currency_curr, "coupon_metaCoup");

		// which currency switch plugin is active
		if (CURRENCY_EXCH == 'woocommerce-aelia-currencyswitcher') { // is_aliea

			// prepend EUR values to multicurrency values in metadata of coupon
			$val_eur['EUR'] = array(
				'coupon_amount' => ''
			);
			$res = array_merge($val_eur, $vals);
			$vals = $res;

			foreach ($vals as $curr_indx => $val) {
				if ($curr_indx == $currency_main) {
					if ($currency_main == $currency_curr) {

						$amount = $this->calc_discount($price_curr, $amount_main);
					} else {

						$price_main = $this->exchange->exchange($price_curr, $currency_curr, $currency_main);

						$amount = $this->calc_discount($price_main, $amount_main);
					}
				} else {

					$price_indx = $this->exchange->exchange($price_curr, $currency_curr, $curr_indx);
					$amount_indx = $this->exchange->exchange($amount_main, $currency_main, $curr_indx);
					// convert the discount
					$amount = $this->calc_discount($price_indx, $amount_indx);
				}

				$vals[$curr_indx]['coupon_amount'] = $amount;
			}

			/* // add EUR to multicurrencies

			if ($currency_main == $currency_curr) {

				$amount = $price_curr - $amount_main;
			} else {

				$price_main = $this->exchange->exchange($price_curr, $currency_curr, $currency_main);

				$amount = $price_main - $amount_main;
			}

			$amount = $amount_main;
			if ($currency_main != 'EUR') {
				$amount = $this->exchange->exchange($amount_main, $currency_main, 'EUR');
			}
			$val_eur['EUR'] = array(
				'coupon_amount' => $amount,
				'minimum_amount' => '',
				'maximum_amount' => ''
			);
			$res = array_merge($val_eur, $vals);
			$vals = $res; */
		} else { // is_WPML

		}

		// returns complete array that defines all Multicurrency values of a coupon
		return $vals;
	}

	/**
	 * Make coupon of the value that will result with the desired fixed price.
	 * Make result with ZERO discount, in cases where the total is smaller than the coupon
	 */
	public function calc_discount($price, $amount)
	{
		if ($price < $amount) {
			$amount = $price;
		} else {
			$amount = $price - $amount;
		}

		return $amount;
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
	public function delete_hidden_coupon_by_code($coupon_code)
	{
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
		if (!function_exists('ve_list_hooks')) {
			ve_debug_log("WARNING: the function ve_list_hooks is not defined!", "error_coupon");

			return;
		}

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
	 * restore the hidden coupon amount by pre-set custom amount, in the current currency
	 */
	/* public function restore_hidd_coup_amount()
	{
		$coupons = WC()->cart->get_applied_coupons();
		ve_debug_log("########################### \n\r
			Updating a hidd coup on curr change " . print_r(WC()->cart, true), "updated_coupon");

		if (!is_array($coupons)) {

			ve_debug_log("WARNING! No coupon applied to this cart", "updated_coupon");

			// no coupons applied!
			return false;
		}

		$coupon_code = '';
		foreach ($coupons as $coupon) {
			if (substr($coupon, 0, 7) == 'fwt_ve_') {

				// get current coupon
				$coupon_code = $coupon;

				break;
			}
		}
		if ($coupon_code == '') {
			ve_debug_log("ERRROR!!! The applied coupon is not a hidden one " . $coupon_code, "updated_coupon");

			// this is not my hidden coupon, DO NOT PROCESS IT in our custom way
			return false;
		}

		// get the coupon in the custom object to process all properties
		$hidden = new Woo_Fixed_Price_Coupons_CouponMeta($coupon_code);

		if (!is_object($hidden)) {

			ve_debug_log("ERRROR !!! Attempt to apply a non-existing coupon " . $coupon_code, "updated_coupon");

			return false;
		}

		ve_debug_log("received coupon to be CONVERTED to another currency: " . $coupon_code, "updated_coupon");

		$coupon_id = $hidden->get_id();

		// // de-apply the coupon		
		// WC()->cart->remove_coupon($coupon_code);
		// ve_debug_log("The coupon is temporarily de-applied! id: " . $coupon_id, "updated_coupon");

		// get the amount in the current currency
		$currency_curr = get_woocommerce_currency();

		$coupon_meta = get_post_meta($coupon_id);

		ve_debug_log("Hidd coup meta " . print_r($coupon_meta['_coupon_currency_data'], true), "updated_coupon");

		// get the multicurrency amount
		foreach ($coupon_meta as $meta_key => $meta_values) {

			foreach ($meta_values as $meta_value) {

				$vals = $meta_value;
				ve_debug_log("for key: " . $meta_key . " meta_value: " . print_r($vals, true), "coup");

				if ($meta_key == '_coupon_currency_data') {

					$vals = unserialize($meta_value);

					ve_debug_log("Hidd coup meta " . print_r($vals, true), "updated_coupon");
				}
			}
		}




		//  make it a coupon amount


		// save coupon


		// apply coupon


	}
 */

	//round cart total up to nearest amount
	function round_total($total)
	{
		$total = round($total, 1);
		return intval(ceil($total));
	}
}

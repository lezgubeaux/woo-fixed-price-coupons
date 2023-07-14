<?php

/**
 * Fired during plugin activation
 *
 * @link       https://framework.tech
 * @since      1.0.0
 *
 * @package    Woo_Fixed_Price_Coupons
 * @subpackage Woo_Fixed_Price_Coupons/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Woo_Fixed_Price_Coupons
 * @subpackage Woo_Fixed_Price_Coupons/includes
 * @author     Vladimir Eric <vladimir@framework.tech>
 */


/**
 * Exchange rate custom gaps, per-currrency
 */

class ExchangeGap
{
    public $exch;
    public $currencies; // array of codes of all active currencies in this WooCommerce

    public function __construct($coupon_code)
    {
        ve_debug_log("################### \r\n
        Exchange rate custom gaps: " . $coupon_code, "gap_coupon_meta");

        // get all active Woo currencies -> $currencies
        $this->active_woo_currencies();
    }

    /**
     * per given currency and amount, get the custom gap, and apply it to the amount
     * returns: the amount, corrected by the particular currency gap
     * returns: false (if bad arguments were passed)
     */
    public function apply_rate($amount, $curr)
    {
        // check if the $amount is positive float
        if (!is_float($amount) || $amount <= 0) {

            ve_debug_log("WARNING: invalid \$amount: " . $amount, "error_coupon");
            return false;
        }

        // check if currency is active in this Woo
        if (!in_array($curr, $this->currencies)) {

            ve_debug_log("WARNING: invalid \$currency: " . $curr, "error_coupon");
            return false;
        }

        $value = $amount * $this->exch[$curr] / 100;

        return $value;
    }

    /**
     * get active woo currencies
     */
    public function active_woo_currencies()
    {

        // get all active Woo currencies -> $currencies
        $this->currencies = '';

        return;
    }
}

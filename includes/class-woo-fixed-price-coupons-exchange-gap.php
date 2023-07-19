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

class Woo_Fixed_Price_Coupons_ExchangeGap
{
    public $gap;
    public $currency; // array of codes of all active currencies in this WooCommerce

    public function __construct()
    {
        // get all active Woo currencies -> $currency
        $this->active_woo_currencies();

        // get all gaps -> $gap
        $this->get_gaps();
    }

    /**
     * per given currency and amount, get the custom gap, and apply it to the amount
     * returns: the amount, corrected by the particular currency gap
     * returns: false (if bad arguments were passed)
     */
    public function apply_gap($amount, $curr)
    {
        ve_debug_log("gap in: " . $amount . " of " . $curr, "gap_coupon_meta");

        // check if the $amount is positive float
        if (!is_float($amount) || $amount <= 0) {

            ve_debug_log("WARNING: invalid \$amount: " . $amount, "error_coupon");
            return false;
        }

        // check if currency is active in this Woo
        if (!in_array($curr, $this->currency)) {

            ve_debug_log("WARNING: invalid \$currency: " . $curr, "error_coupon");
            return false;
        }

        // increase the value by the particular currency gap
        $value = $amount * ($this->gap[$curr] + 1);
        ve_debug_log("gap out: " . $value . " of " . $curr, "gap_coupon_meta");

        return round($value, 2);
    }

    /** 
     * get all defined gaps for currency exchange
     */
    public function get_gaps()
    {
        // get from saved options woo_fpc_gap_COD(e)

        foreach ($this->currency as $val) {
            $this->gap[$val] = get_option('woo_fpc_gap_' . $val, 0);
        }

        ve_debug_log("Custom exch gaps: " . print_r($this->gap, true), "gap_list");
    }

    /**
     * get active woo currencies
     */
    public function active_woo_currencies()
    {
        // get all WOO currencies
        $all_curr = get_woocommerce_currencies();

        $active_curr = ['EUR']; // base currency - other are added below

        // get all active Woo currencies -> $currency
        foreach ($all_curr as $code => $curr) {
            $res = apply_filters('wc_aelia_cs_convert', 9999, $code, 'EUR');

            ve_debug_log("Creating active curr list " . $code . " " . $res, "gap_coupon");

            if ($res != 9999) {
                $active_curr[] = $code;
            }
        }

        $this->currency = $active_curr;
        ve_debug_log("Active curr: " . print_r($this->currency, true), "gap_list");

        return;
    }
}

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
    public function apply_gap($amount, $curr, $do_gap = 1)
    {
        // check if the $amount is positive float
        /*         if (!is_float($amount) || $amount <= 0) {

            ve_debug_log("WARNING: invalid \$amount: " . print_r($amount, true), "error_coupon");
            return false;
        } */

        // check if currency is active in this Woo
        if (!in_array($curr, $this->currency)) {

            ve_debug_log("WARNING: invalid \$currency: " . print_r($curr, true), "error_coupon");
            return false;
        }

        ve_debug_log("gap in: " . $amount . " of " . $curr, "gap_coupon");

        // increase the value by the particular currency gap
        $value = $amount * (1 + $this->gap[$curr] * $do_gap);
        ve_debug_log("gap out: " . $value . " of " . $curr, "gap_coupon");

        return round($value, 2);
    }

    /** 
     * get all defined gaps for currency exchange
     */
    public function get_gaps()
    {
        if (!is_array($this->currency)) {

            ve_debug_log("ERROR!!! Enabled currencies not found. Check the plugin code", "error_coupon");

            wp_die();
        }
        // get from saved options woo_fpc_gap_COD(e)
        foreach ($this->currency as $val) {
            $this->gap[$val] = get_option('woo_fpc_gap_' . $val, 0);
        }
    }

    /**
     * get active woo currencies
     */
    public function active_woo_currencies()
    {
        if (CURRENCY_EXCH == 'Aelia') {
            if (class_exists('Aelia\WC\CurrencySwitcher\WC_Aelia_CurrencySwitcher')) {

                $currency_switcher = Aelia\WC\CurrencySwitcher\WC_Aelia_CurrencySwitcher::settings();
                $enabled_currencies = $currency_switcher->get_enabled_currencies();
            } else {
                ve_debug_log("ERROR! Aelia Currency Switcher is either missing, or it's newer version uses different classes.", "error_coupon");
            }

            // add EUR on top
            $active_curr = array_merge(['EUR'], $enabled_currencies);
        } else if (CURRENCY_EXCH == 'WPML') {

            ve_debug_log("PLUGIN " . CURRENCY_EXCH, "coup_exch");

            // woocommerce-multilingual manages currency exchange rate
            $wcml_settings = get_option('_wcml_settings');
            ve_debug_log("wcml sett " . print_r($wcml_settings, true), "coup_exch");

            $active_curr = $wcml_settings['currencies_order'];
        } else {
            $active_curr = ['XXX'];

            ve_debug_log("No acceptable Multicurrency plugin found", "error_coupon");
        }

        /* if (!is_array($enabled_currencies)) {

            ve_debug_log("ERROR!!! Enabled currencies not found. Check the plugin code" . print_r($enabled_currencies, true), "error_coupon");

            wp_die();
        } */

        ve_debug_log("All enabled currencies: " . print_r($active_curr, true), "coup_exch");

        $this->currency = $active_curr;

        return;
    }
}

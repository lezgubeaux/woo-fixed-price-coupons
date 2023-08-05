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
 * coupon class
 */

global $woocommerce;

class Woo_Fixed_Price_Coupons_CouponMeta extends WC_Coupon
{
    public $meta;
    public $meta_val;
    public $meta_all;

    public function __construct($coupon_code)
    {

        parent::__construct($coupon_code); // get native coupon class

        // which currency switch plugin is active
        if (CURRENCY_EXCH == 'Aelia') {
            $meta_currency_key = '_coupon_currency_data';
        } elseif (CURRENCY_EXCH == 'WPML') {
            $meta_currency_key = 'shop_coupon_multicurrency';
        } else {
            ve_debug_log("WARNING!!! No acceptable Multicurrency plugin found! ", "error_coupon");
        }

        $this->meta = ['', ''];
        foreach ($this->meta_data as $key => $meta_orig) {

            ve_debug_log("Metadata found: " . $key . " / " . print_r($meta_orig, true), "coupon_meta");

            $this->meta_all = $meta_orig->get_data("current_data");

            $id = $this->meta_all['id'];

            $this->find_nonempty($this->meta_all['value'], $id);
        }
    }

    private function find_nonempty($vals, $id)
    {

        // if no multicurrency value found, this is EUR-only coupon

        if (is_array($vals)) {
            // if any multicurrency value found, use its CODE as _main
            foreach ($vals as $key => $val) {
                if ($val['coupon_amount']) {
                    ve_debug_log("** " . $id . " key/val " . $key . " " . $val['coupon_amount'], "coupon_meta");
                    $this->meta[0] = $val['coupon_amount'];
                    $this->meta[1] = $key;

                    break;
                }
            }
            // if no multicurrency value found, this is EUR-only coupon
            if (strlen($this->meta[1]) != 3) {

                $coupon_amount = $this->data["amount"];

                ve_debug_log("============ From couponMeta: id = " . $id . " coup: " . print_r($coupon_amount, true), "coupon_meta");

                $this->meta[0] = $coupon_amount;
                $this->meta[1] = 'EUR';
            }
        }
        return;
    }
}

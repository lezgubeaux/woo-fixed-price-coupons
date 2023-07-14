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
        ve_debug_log("################### \r\n
        Coupon Meta Class: " . $coupon_code, "hidd_coupon_meta");

        parent::__construct($coupon_code); // get native coupon class
        $this->meta = ['', ''];
        if (isset($this->meta_data[0])) {
            $this->meta_all = $this->meta_data[0]->get_data("current_data");
            ve_debug_log(print_r($this->meta_all, true), "hidd_coupon_meta");
            $this->find_nonempty($this->meta_all['value']);
        }
    }

    private function find_nonempty($vals)
    {
        if (!is_array($vals)) {
            return;
        }

        foreach ($vals as $key => $val) {
            if ($val['coupon_amount']) {
                $this->meta[0] = $val['coupon_amount'];
                $this->meta[1] = $key;
                break;
            }
        }
        return;
    }
}

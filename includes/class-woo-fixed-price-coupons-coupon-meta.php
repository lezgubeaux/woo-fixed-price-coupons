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
    public $meta = array();
    public $meta_val;
    public $meta_all;

    public function __construct($coupon_code)
    {
        parent::__construct($coupon_code); // get native coupon class
        if ($this->meta_data[0]) {
            $this->meta_all = $this->meta_data[0]->get_data("current_data");
            $this->find_nonempty($this->meta_all['value']);
        }
    }

    private function find_nonempty($vals)
    {

        $this->meta = ['', ''];

        foreach ($vals as $key => $val) {
            if ($val['coupon_amount']) {
                $this->meta[0] = $val['coupon_amount'];
                $this->meta[1] = $key;
                break;
            }
        }
    }
}

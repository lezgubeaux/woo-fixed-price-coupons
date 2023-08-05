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
 * Multiurrency values of Coupons (creates metadata, fields, and etc)
 */

class Woo_Fixed_Price_Coupons_Multicurrency_Amounts
{
    public $post_id;
    public $enabled_curr;

    /**
     * init
     */
    public function __construct($post_id, $post)
    {
        $this->post_id = $post_id;

        // get all enabled currencies
        $curr = new Woo_Fixed_Price_Coupons_ExchangeGap;
        $this->enabled_curr = $curr->currency;
    }

    /**
     * define metadata for Multicurrency amounts of a coupon
     */
    public function add_multicurrency_meta_fields()
    {
        $post_id = $this->post_id;
        // Get the post type of the current post
        $post_type = get_post_type($post_id);

        // Check if we are in the admin panel to avoid executing on the front end
        // Check if coupon CPT
        if (is_admin() && $post_type === 'shop_coupon') {

            // add multi curr metadata per each curr
            $enabled_curr = $this->enabled_curr;
            ve_debug_log($enabled_curr, "multi_meta");

            $metadata = array();

            foreach ($enabled_curr as $curr) {

                $metadata[$curr] = '';
            }
            // Add metadata 'shop_coupon_multicurrency' to the 'shop_coupon' post type
            add_post_meta(
                $post_id,
                'shop_coupon_multicurrency',
                $metadata,
                true
            );
        }
    }
}

<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://framework.tech
 * @since      1.0.0
 *
 * @package    Woo_Fixed_Price_Coupons
 * @subpackage Woo_Fixed_Price_Coupons/admin/partials
 */
?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<h1>Fixed Price Coupons</h1>
This plugin manages special type of coupons. Our coupons' amount is the same amount you will see in total (instead of being deducted from it).<br /><br />

It is mandatory that your installation uses either of the two multicurrency plugins: Aelica Currency Switcher or WPML Multicurrency. The plugin will use the exchange rate set within either of these plugins.<br /><br />

Exchange rate gap needs to be set within the <a href="<?= get_admin_url(); ?>admin.php?page=woo-fixed-price-coupons-settings"><strong>Settings Page<strong></a>. No gap should be set within Aelia or WPML multicurrency plugins!
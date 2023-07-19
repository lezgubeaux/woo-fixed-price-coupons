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

<div class="wrap">
    <div id="icon-themes" class="icon32"></div>
    <h2>Fixed Price Coupons - Settings</h2>
    <!--NEED THE settings_errors below so that the errors/success messages are shown after submission - wasn't working once we started using add_menu_page and stopped using add_options_page so needed this-->
    <?php settings_errors(); ?>
    <em>Values must be in decimal numbers (1% = 0.01, 2.5% = 0.025)...</em>
    <form method="POST" action="options.php">
        <?php
        settings_fields('woo_fpc_general_settings');
        do_settings_sections('woo_fpc_general_settings');
        ?>
        <?php submit_button(); ?>
    </form>
</div>
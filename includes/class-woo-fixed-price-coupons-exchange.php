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
 * Currency exchange procedures
 */

class Woo_Fixed_Price_Coupons_Exchange
{
    /**
     * exchange the currency, with gap and active currency options
     */
    public function exchange($value, $curr_from, $curr_to = '')
    {
        // exchange is done in two steps: 1) to EUR, 2) to target currency
        if (!$curr_to) {
            $curr_to = get_woocommerce_currency();
        }
        ve_debug_log("2.0 to be exch: " . $value . " " . $curr_from . " " . $curr_to . " ", "coup");

        $amount = $this->exch_from_to($value, $curr_from, $curr_to);

        ve_debug_log("2.1 exch final result with gap: " . $amount, "coup");

        return $amount;
    }

    /**
     * exchange an amount from one currency to another
     */
    public function exch_from_to($amount, $from, $to)
    {
        $amount = floatval($amount);

        ve_debug_log("3.0 amount to be exch: " . $amount, "coup");
        $gap = new Woo_Fixed_Price_Coupons_ExchangeGap;

        if (CURRENCY_EXCH == 'Aelia') {

            if ($to == 'EUR') {
                // if EUR, no 2) conversion

                ve_debug_log("3.1 amount to be UN-gapped: " . $amount, "coup");
                // remove initially added gap
                $res = $gap->apply_gap($amount, $from, -1);

                ve_debug_log("3.2 amount after un-gapped: " . $res, "coup");
                // 											amount	from 		to
                $val = apply_filters('wc_aelia_cs_convert', $res, $from, 'EUR');
                ve_debug_log("3.3 E 1/1 Amount exchanged 
					from " . $from . "=" . $amount .
                    " to " . $to . "=" . $val .
                    "...with gap included", "coup");

                return $val;
            }

            if ($from != 'EUR') {
                // first conversion: to EUR
                // remove initially added gap
                $res = $gap->apply_gap($amount, $from, -1);
                ve_debug_log("3.1 to be gapped: " . $res, "coup");

                $val = apply_filters('wc_aelia_cs_convert', $res, $from, 'EUR');
                ve_debug_log("3.2 E 1/2 firstly, exchanged to EUR:
                from " . $from . "=" . $amount .
                    " to EUR =" . $val .
                    "...with gap removed", "coup");
            } else {
                $val = $amount;
            }

            // second conversion: EUR to current currency
            $res = apply_filters('wc_aelia_cs_convert', $val, 'EUR', $to);
            ve_debug_log("3.3 E 2/2 secondly, exchanged to: " .
                $to . " = " . $res, "coup");

            $val = $gap->apply_gap($res, $to, 1);
            ve_debug_log("...with gap added " . $val, "coup");

            return $val;
        } else if (CURRENCY_EXCH == 'WPML') {

            // woocommerce-multilingual manages currency exchange rate
            $wcml = new WCML_Multi_Currency;
            $exch_rates = $wcml->get_exchange_rates();

            // ve_debug_log("WCML exch. rates: " . print_r($exch_rates, true), "coup");
            // apply_filters( 'wcml_exchange_rates', $this->exchange_rates )

            if ($to == 'EUR') {

                ve_debug_log("3.1 amount to be gapped: " . $amount, "coup");
                // remove initially added gap
                $res = $gap->apply_gap($amount, $from, -1);
                ve_debug_log("3.2 amount_main_orig that WAS gapped: " . $res, "coup");
                // if EUR, no 2) conversion
                $val = $res / $exch_rates[$from];
                ve_debug_log("3.3 E 1/1 Amount exchanged 
					from " . $from . "=" . $amount .
                    " to " . $to . "=" . $val .
                    "...with gap included", "coup");

                return $val;
            }

            if ($from != 'EUR') {
                // first conversion: to EUR
                // remove initially added gap
                $res = $gap->apply_gap($amount, $from, -1);
                ve_debug_log("3.1 to be gapped: " . $res, "coup");

                $val = $res / $exch_rates[$from];
                ve_debug_log("3.2 E 1/2 firstly, exchanged to EUR:
                from " . $from . "=" . $amount .
                    " to EUR =" . $val .
                    "...with gap removed", "coup");
            } else {
                $val = $amount;
            }

            // second conversion: EUR to current currency
            $res = $val * $exch_rates[$to];
            ve_debug_log("3.3 E 2/2 secondly, exchanged to: " .
                $to . " = " . $res, "coup");

            $val = $gap->apply_gap($res, $to, 1);
            ve_debug_log("...with gap " . $val, "coup");

            return $val;


















            /* if ($from == 'EUR') {
                // convert from EUR
                $val = $amount * $exch_rates[$to];

                $res = $gap->apply_gap($val, $to, 1 * $do_gap);
                ve_debug_log("...with gap " . $res, "coup");

                return $res;
            } else if ($to == 'EUR') {
                // convert to EUR
                $val = $amount / $exch_rates[$from];

                $res = $gap->apply_gap($val, $to, -1 * $do_gap);

                return $res;
            } else {
                // convert to EUR
                $val = $amount / $exch_rates[$from];

                $res = $gap->apply_gap($val, $to, -1 * $do_gap);

                // convert from EUR
                $val = $res * $exch_rates[$to];

                $res = $gap->apply_gap($val, $to, 1 * $do_gap);

                return $res;
            } */
        }
    }
}

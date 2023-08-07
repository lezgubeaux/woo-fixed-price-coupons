=== Plugin Name ===
Contributors: (this should be a list of wordpress.org userid's)
Donate link: https://framework.tech
Tags: comments, spam
Requires at least: 3.0.1
Tested up to: 3.4
Stable tag: 4.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Here is a short description of the plugin.  This should be no more than 150 characters.  No markup here.

== Description ==

This is the long description.  No limit, and you can use Markdown (as well as in the following sections).

For backwards compatibility, if this section is missing, the full length of the short description will be used, and
Markdown parsed.

A few notes about the sections above:

*   "Contributors" is a comma separated list of wp.org/wp-plugins.org usernames
*   "Tags" is a comma separated list of tags that apply to the plugin
*   "Requires at least" is the lowest version that the plugin will work on
*   "Tested up to" is the highest version that you've *successfully used to test the plugin*. Note that it might work on
higher versions... this is just the highest one you've verified.
*   Stable tag should indicate the Subversion "tag" of the latest stable version, or "trunk," if you use `/trunk/` for
stable.

    Note that the `readme.txt` of the stable tag is the one that is considered the defining one for the plugin, so
if the `/trunk/readme.txt` file says that the stable tag is `4.3`, then it is `/tags/4.3/readme.txt` that'll be used
for displaying information about the plugin.  In this situation, the only thing considered from the trunk `readme.txt`
is the stable tag pointer.  Thus, if you develop in trunk, you can update the trunk `readme.txt` to reflect changes in
your in-development version, without having that information incorrectly disclosed about the current stable version
that lacks those changes -- as long as the trunk's `readme.txt` points to the correct stable tag.

    If no stable tag is provided, it is assumed that trunk is stable, but you should specify "trunk" if that's where
you put the stable version, in order to eliminate any doubt.

== Installation ==

This section describes how to install the plugin and get it working.

e.g.

1. Upload `woo-fixed-price-coupons.php` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Place `<?php do_action('plugin_name_hook'); ?>` in your templates

== Frequently Asked Questions ==

= A question that someone might have =

An answer to that question.

= What about foo bar? =

Answer to foo bar dilemma.

== Screenshots ==

1. This screen shot description corresponds to screenshot-1.(png|jpg|jpeg|gif). Note that the screenshot is taken from
the /assets directory or the directory that contains the stable readme.txt (tags or trunk). Screenshots in the /assets
directory take precedence. For example, `/assets/screenshot-1.png` would win over `/tags/4.3/screenshot-1.png`
(or jpg, jpeg, gif).
2. This is the second screen shot

== Changelog ==

= 1.4.1 =
~ for non-Aelia currency plugin, update Cart Discount from Coupon Multicurrency values

= 1.4.0 =
fix minor calculation glitches

Original coupon code preserved as coupon_description.
Unique code for temporary coupons brought back (for the cases where mulitple users use the same coupon simultaniously)

= 1.3.9 =
apply WPML exchange rate, our custom exchange gaps, and custom multicurrency values of a coupon - to all already developed coupon management processes

= 1.3.8 =
add custom multicurrency values of a coupon
add WPML Multicurrency dynamic list of activate currencies
dynamic list of activate currencies of Aelia - already added

custom gap system completed

Coupons now work together with the custom discount system (previously developed by the team)

= 1.3.7 =
Take discount price in calculation (work on the product total, not cart total = see functions!!!)

= 1.3.6 =

Multicurrency Metadata and Metaboxes added to edit coupon page.

= 1.3.5 =
Level the total to 0 if it goes to negative number

= 1.3.4 =
Adding exchange currency gap via php array (temporary solution, for testing only).

Creating and testing the gap system.

= 1.3.3 =
Fixed some bugs shown on a different installation (that does not use Aelia Currency Switcher to add multicurrency value to a coupon)

= 1.3.2 =
Custom exchange rate gap started. It uses any given amount and currency code to add predefined gap and return the amount with the gap included

= 1.3.1 =
"Aelia Currency Switcher or WPML Multicurrency" feature now works (up to WMPL exch. rate pull)

= 1.3.0 =
Works with either Aelia Currency Switcher or WPML Multicurrency (detecting which is active, and using proper function to get the exchange rate)

Added: settings page
A gap (exchange rate markup) is set per each currency. The saved option has a top priority over switch-currency plugin that is already in use (so the gap with them needs to be set to 0)

= 1.2.3 =
Custom calculation of the coupon discount completely removed. The desired total achieved by creating an mirror-coupon, hidden from users, who's ammount is calculated to give out the desired total, but within the standard Woo coupon calculation.
This way, when an Order is sent to CRM or re-calculated in the Dashboard - the Total remains as wanted.

All hooks related to forcing a change in the Cart (excepts for the coupon) - are removed now!

= 1.2.2 =
ve_debug_log function (normally located in an mu-plugin) added in-the-plugin (to prevent errors, if mu-plugin not present)

= 1.2.1 =
Euro-based coupon had a flaw in calculation - now fixed.
A few more log breakpoints added to ....log_fixed-coupon

= 1.2 =
all working as requested.
The previous fixes are completely removed from -child files.
The plugin works independently.

= 1.1 =
- functions (general steps)
- test output function

= 1.0 =
* Boilerplate setup

=

Here's a link to [WordPress](http://wordpress.org/ "Your favorite software") and one to [Markdown's Syntax Documentation][markdown syntax].
Titles are optional, naturally.

[markdown syntax]: http://daringfireball.net/projects/markdown/syntax
            "Markdown is what the parser uses to process much of the readme file"

Markdown uses email style notation for blockquotes and I've been told:
> Asterisks for *emphasis*. Double it up  for **strong**.

`<?php code(); // goes in backticks ?>`
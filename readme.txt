=== PMPro Variable Pricing ===
Contributors: strangerstudios
Tags: pmpro, membership, donate, donations, charity, charities
Requires at least: 3.0
Tested up to: 4.8.3
Stable tag: .4

Allow customers to set their own price when checking out for your membership levels.

== Description ==
This plugin requires Paid Memberships Pro. 

== Installation ==

1. Upload the `pmpro-variable-pricing` directory to the `/wp-content/plugins/` directory of your site.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Edit the levels you want to add variable pricing to and set the "Variable Pricing" settings.

== Frequently Asked Questions ==

= I found a bug in the plugin. =

Please post it in the issues section of GitHub and we'll fix it as soon as we can. Thanks for helping. https://github.com/strangerstudios/pmpro-variable-pricing/issues

== Changelog ==

= .4 =
* Bug Fix: Allow blank variable price input (i.e. use the minimum price)
* Feature: Properly formatted tranlatable text
* Feature: Membership level setting to skip printing an amount in the Variable Price input box on the Checkout page when loading
* Feature: No longer embedding JS in sources/page.
* Feature: Priority of JS register/enqueue operation means you can unhook the Variable Prices JavaScript if needed
* Feature: Add translation domain labels

= .3 =
* Now storing price in session for offsite gateways like PayPal and 2Checkout.

= .2 =
* Updated JS logic to hide/show billing to work for PayPal, PayPal Express, and Stripe (with no billing fields) gateway options.

= .1 =
* This is the initial version of the plugin.
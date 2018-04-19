=== Paid Memberships Pro - Variable Pricing Add On ===
Contributors: strangerstudios
Tags: paid memberships pro, pmpro, membership, donate, donations, charity, charities
Requires at least: 4.0
Tested up to: 4.9.5
Stable tag: .4.1

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

= .4.2 =
* BUG FIX: Fixed fatal error crash when PMPro is not activated.

= .4.1 =
* ENHANCEMENT: French translation files. (Thanks, Alfonso Sánchez Uzábal)
* ENHANCEMENT: Consistent plugin titles and URLs
* ENHANCEMENT: WordPress Coding Standards

= .4 =
* BUG FIX: Allow blank variable price input (i.e. use the minimum price)
* BUG FIX/FEATURE: Fixed logic for hiding/showing billing fields if the price is free or not.
* FEATURE: Properly formatted translatable text
* FEATURE: Added translation domain labels
* FEATURE: Added language file load (when applicable)
* FEATURE: Added suggested price setting.
* FEATURE: No longer embedding JS in frontend sources/page.
* FEATURE: Priority of JS register/enqueue operation means you can unhook the Variable Prices JavaScript if needed

= .3.1 =
* BUG: Now hiding Variable Pricing options on checkout review page.

= .3 =
* Now storing price in session for offsite gateways like PayPal and 2Checkout.

= .2 =
* Updated JS logic to hide/show billing to work for PayPal, PayPal Express, and Stripe (with no billing fields) gateway options.

= .1 =
/*
 *
 *  Copyright (c) 2017. - Stranger Studios, LLC - Thomas Sjolshagen <thomas@eighty20results.com>
 *  ALL RIGHTS RESERVED
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


//some vars for keeping track of whether or not we show billing
var pmpro_gateway_billing;
var pmpro_pricing_billing;

if ( pmpro_require_billing == null ) {
    var pmpro_require_billing = true;
}
//this script will hide show billing fields based on the price set
jQuery(document).ready(function() {
    "use strict";
    var variablePricing = {
        init: function() {

            pmpro_gateway_billing = pmprovp.settings.gateway_billing;
            pmpro_pricing_billing = pmprovp.settings.pricing_billing;

            this.price_timer = null;
            this.gateway = jQuery('input[name=gateway]');

            var self = this;

            // Exit if there are payment choices available
            if ( jQuery('#pmpro_payment_method').length > 0 ) {
                return;
            }

            //bind check to price field
            jQuery('#price').bind('keyup change', function() {
                self.price_timer = setTimeout(self.checkForFree, 500);
            });

            if( self.gateway.val() !== '' )
            {
                self.gateway.bind('click', function() {
                    self.price_timer = setTimeout(self.checkForFree, 500);
                });
            }

            //check when page loads too
            self.checkForFree();
        },
        checkForFree: function() {

            var self = this;
            var priceElem = jQuery('#price');
            var addressFields = jQuery('#pmpro_billing_address_fields');
            var paymentInfo = jQuery('#pmpro_payment_information_fields');

            var price = parseFloat(priceElem.val());

            //does the gateway require billing?
            if(self.gateway.length > 0 )
            {
                var no_billing_gateways = ['paypalexpress', 'twocheckout'];
                var gateway = self.gateway.is(':checked').val();

                if(no_billing_gateways.indexOf(gateway) > -1) {
                    pmpro_gateway_billing = false;
                } else {
                    pmpro_gateway_billing = true;
                }
            }

            //is there a price?
            if(true === isNaN(price) && false === priceElem.is(':empty') ) {
                pmpro_pricing_billing = false;
            } else {
                pmpro_pricing_billing = true;
            }

            //figure out if we should show the billing fields
            if(true === pmpro_gateway_billing && true === pmpro_pricing_billing)
            {
                addressFields.show();
                paymentInfo.show();
                pmpro_require_billing = true;
            }
            else
            {
                addressFields.hide();
                paymentInfo.hide();
                pmpro_require_billing = false;
            }
        }
    }

    variablePricing.init();
});
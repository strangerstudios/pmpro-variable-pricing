<?php
/*
Plugin Name: Paid Memberships Pro - Variable Pricing Add On
Plugin URI: https://www.paidmembershipspro.com/add-ons/variable-pricing-add-on/
Description: Allow customers to set their own price when checking out for your membership levels.
Version: .4.2
Author: Paid Memberships Pro
Author URI: https://www.paidmembershipspro.com
Text Domain: pmpro-variable-pricing
*/

/*
	The Plan
	- Override level cost text on checkout to show text field to set price.
	- Use that price when checking out.
	- Price overrides the initial payment and any billing amount for the level.
	- Leaves trial, billing cycle, and expiration stuff alone.
	- Add "min price" and "max price" fields to edit level page.
	- Set price is the "suggested price"
*/

/*
	Load plugin textdomain.
*/
function pmprovp_load_textdomain() {
	load_plugin_textdomain( 'pmpro-variable-pricing', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'pmprovp_load_textdomain' );

/**
 * Get settings for a specified level.
 */
function pmprovp_get_settings( $level_id ) {
	$defaults = array(
		'variable_pricing' => 0,
		'min_price'        => '',
		'max_price'        => '',
		'suggested_price'  => '',
	);
	$settings = get_option( "pmprovp_{$level_id}", $defaults );
	$settings = array_merge( $defaults, $settings );  // make sure newly added settings have defaults appended

	return $settings;
}

/*
	Min Price and Max Price Fields on the edit levels page
*/
// fields on edit page
function pmprovp_pmpro_membership_level_after_other_settings() {
	global $pmpro_currency_symbol;
	$level_id = intval( $_REQUEST['edit'] );
	if ( $level_id > 0 ) {
		$vpfields         = pmprovp_get_settings( $level_id );
		$variable_pricing = $vpfields['variable_pricing'];
		$min_price        = $vpfields['min_price'];
		$max_price        = $vpfields['max_price'];
		$suggested_price  = $vpfields['suggested_price'];
	} else {
		$variable_pricing = 0;
		$min_price        = '';
		$max_price        = '';
		$suggested_price  = '';
	}
?>
<h3 class="topborder"><?php _e( 'Variable Pricing', 'pmpro-variable-pricing' ); ?></h3>
<p><?php _e( 'If variable pricing is enabled, users will be able to set their own price at checkout. That price will override any initial payment and billing amount values you set on this level. You can set the minimum, maxium, and suggested price for this level.', 'pmpro-variable-pricing' ); ?></p>

<table>
<tbody class="form-table">
	<tr>
		<th scope="row" valign="top"><label for="pmprovp_variable_pricing"><?php _e( 'Enable:', 'pmpro-variable-pricing' ); ?></label></th>
		<td>
			<input type="checkbox" name="variable_pricing" id="pmprovp_variable_pricing" value="1" <?php checked( $variable_pricing, '1' ); ?> /> <label for="pmprovp_variable_pricing"><?php _e( 'Enable Variable Pricing', 'pmpro-variable-pricing' ); ?></label>
		</td>
	</tr>
	<tr class="pmprovp_setting">
		<th scope="row" valign="top"><label for="pmprovp_min_price"><?php _e( 'Min Price:', 'pmpro-variable-pricing' ); ?></label></th>
		<td>
			<?php echo $pmpro_currency_symbol; ?><input type="text" name="min_price" id="pmprovp_min_price" value="<?php echo esc_attr( $min_price ); ?>" />
		</td>
	</tr>
	<tr class="pmprovp_setting">
		<th scope="row" valign="top"><label for="pmprovp_max_price"><?php _e( 'Max Price:', 'pmpro-variable-pricing' ); ?></label></th>
		<td>
			<?php echo $pmpro_currency_symbol; ?><input type="text" name="max_price" id="pmprovp_max_price" value="<?php echo esc_attr( $max_price ); ?>" />
			<?php _e( 'Leave this blank to allow any maximum amount.', 'pmpro-variable-pricing' ); ?>
		</td>
	</tr>
	<tr class="pmprovp_setting">
		<th scope="row" valign="top"><label for="pmprovp_suggested_price"><?php _e( 'Suggested Price:', 'pmpro-variable-pricing' ); ?></label></th>
		<td>
			<?php echo $pmpro_currency_symbol; ?><input type="text" name="suggested_price" id="pmprovp_suggested_price" value="<?php echo esc_attr( $suggested_price ); ?>" />
			<?php _e( 'You may leave this blank.', 'pmpro-variable-pricing' ); ?>
		</td>
	</tr>
</tbody>
</table>
<script>
	jQuery(document).ready(function(){
		function pmprovp_toggleSettings() {
			var pmprovp_enabled = jQuery('#pmprovp_variable_pricing:checked').val();

			if(typeof pmprovp_enabled == 'undefined') {
				//disabled
				jQuery('tr.pmprovp_setting').hide();
			} else {
				//enabled
				jQuery('tr.pmprovp_setting').show();
			}
		}

		jQuery('#pmprovp_variable_pricing').change(function(){pmprovp_toggleSettings()});

		pmprovp_toggleSettings();
	});
</script>
<?php
}
add_action( 'pmpro_membership_level_after_other_settings', 'pmprovp_pmpro_membership_level_after_other_settings' );

// save level cost text when the level is saved/added
function pmprovp_pmpro_save_membership_level( $level_id ) {
	$variable_pricing = intval( $_REQUEST['variable_pricing'] );
	$min_price        = preg_replace( '[^0-9\.]', '', $_REQUEST['min_price'] );
	$max_price        = preg_replace( '[^0-9\.]', '', $_REQUEST['max_price'] );
	$suggested_price  = preg_replace( '[^0-9\.]', '', $_REQUEST['suggested_price'] );

	update_option(
		'pmprovp_' . $level_id, array(
			'variable_pricing' => $variable_pricing,
			'min_price'        => $min_price,
			'max_price'        => $max_price,
			'suggested_price'  => $suggested_price,
		)
	);
}
add_action( 'pmpro_save_membership_level', 'pmprovp_pmpro_save_membership_level' );

/*
	Show form at checkout.
*/
// override level cost text on checkout page
function pmprovp_pmpro_level_cost_text( $text, $level ) {
	global $pmpro_pages;
	if ( is_page( $pmpro_pages['checkout'] ) && !did_action( 'pmpro_after_checkout' ) ) {
		$vpfields = pmprovp_get_settings( $level->id );
		if ( ! empty( $vpfields ) && ! empty( $vpfields['variable_pricing'] ) ) {
			$text = '';
		}
	}

	return $text;
}
add_filter( 'pmpro_level_cost_text', 'pmprovp_pmpro_level_cost_text', 10, 2 );

// show form
function pmprovp_pmpro_checkout_after_level_cost() {
	global $pmpro_level, $gateway, $pmpro_review, $pmpro_currencies, $pmpro_currency, $pmpro_currency_symbol;

	// get variable pricing info
	$vpfields = pmprovp_get_settings( $pmpro_level->id );

	// no variable pricing? just return
	if ( empty( $vpfields ) || empty( $vpfields['variable_pricing'] ) || $pmpro_review ) {
		return;
	}

	// okay, now we're showing the form
	$min_price       = $vpfields['min_price'];
	$max_price       = $vpfields['max_price'];
	$suggested_price = $vpfields['suggested_price'];


	if ( isset( $_REQUEST['price'] ) ) {
		$price = preg_replace( '[^0-9\.]', '', $_REQUEST['price'] );
	} else {
		$price = $suggested_price;
	}

	// setup price text description based on price ranges
	if ( ! empty( $max_price ) && ! empty( $min_price ) ) {
		$price_text_description = sprintf(
			__( 'Enter a price between %1$s and %2$s.', 'pmpro-variable-pricing' ),
			esc_html( pmpro_formatPrice( $vpfields['min_price'] ) ),
			esc_html( pmpro_formatPrice( $vpfields['max_price'] ) )
		);
	} elseif( ! empty( $min_price ) && empty( $max_price ) ) {
		$price_text_description = sprintf(
			__( 'Enter a minimum price of %s or higher.', 'pmpro-variable-pricing' ),
			esc_html( pmpro_formatPrice( $vpfields['min_price'] ) )
		);
	} elseif( ! empty( $max_price ) && empty( $min_price ) ) {
		$price_text_description = sprintf(
			__( 'Enter a price of %s or lower.', 'pmpro-variable-pricing' ),
			esc_html( pmpro_formatPrice( $vpfields['max_price'] ) )
		);
	} else {
		$price_text_description = __( 'Enter a price for your membership', 'pmpro-variable-pricing' );
	}

	/**
	 * @filter pmpropvp_checkout_price_description - Filter to modify the variable price description text
	 * @param string $price_text_description
	 */
	$price_text_description = apply_filters( 'pmpropvp_checkout_price_description', $price_text_description );
		
	if ( empty( $pmpro_currencies[$pmpro_currency]['position'] ) || $pmpro_currencies[$pmpro_currency]['position'] == 'left' ) {
		$price_text = sprintf(
			__( 'Your price: %s', 'pmpro-variable-pricing' ),
			esc_html( $pmpro_currency_symbol )
		);
	} else {
		$price_text = __( 'Your price:', 'pmpro-variable-pricing' );
	}
	
	/**
	 * @filter pmprovp_checkout_price_input_label - Filter to modify the label for the Variable Price input box on the checkout page
	 * @param string $price_text
	 */
	$price_text = apply_filters( 'pmprovp_checkout_price_input_label', $price_text );

?>
<div class="pmprovp">
	<p class="pmprovp_price_text_description"><?php esc_html_e( $price_text_description ); ?></p>
	<p class="pmprovp_price_input"><?php esc_html_e( $price_text ); ?> <input type="text" id="price" name="price" size="10" value="<?php esc_attr_e( $price ); ?>" style="width:auto;" /> <?php if ( !empty( $pmpro_currencies[$pmpro_currency]['position'] ) &&  $pmpro_currencies[$pmpro_currency]['position'] == 'right' ) { echo $pmpro_currency_symbol; } ?>
	<span id="pmprovp-warning" class="pmpro_message pmpro_alert" style="display:none;"><small><?php echo $price_text_description; ?></small></span></p>
</div> <!-- end .pmprovp -->
<?php
}
add_action( 'pmpro_checkout_after_level_cost', 'pmprovp_pmpro_checkout_after_level_cost' );

// set price
function pmprovp_pmpro_checkout_level( $level ) {
	if ( isset( $_REQUEST['price'] ) ) {
		$price = preg_replace( '[^0-9\.\,]', '', $_REQUEST['price'] );
	}

	if ( isset( $price ) ) {
		$level->initial_payment = $price;

		if ( $level->billing_amount > 0 ) {
			$level->billing_amount = $price;
		}
	}

	return $level;
}
add_filter( 'pmpro_checkout_level', 'pmprovp_pmpro_checkout_level' );

/**
 * Check if price is between min and max value.
 * If no max value is set, set it to unlimited.
 */
function pmprovp_pmpro_registration_checks( $continue ) {
	// only bother if we are continuing already
	if ( $continue ) {
		global $pmpro_currency_symbol, $pmpro_msg, $pmpro_msgt;

		// was a price passed in?
		if ( isset( $_REQUEST['price'] ) ) {
			// get values
			$level_id = intval( $_REQUEST['level'] );
			$vpfields = pmprovp_get_settings( $level_id );

			// make sure this level has variable pricing
			if ( empty( $vpfields ) || empty( $vpfields['variable_pricing'] ) ) {
				$pmpro_msg  = __( "Error: You tried to set the price on a level that doesn't have variable pricing. Please try again.", 'pmpro-variable-pricing' );
				$pmpro_msgt = 'pmpro_error';
			}

			// get price
			$price = preg_replace( '[^0-9\.]', '', $_REQUEST['price'] );

			// check that the price falls between the min and max
			if ( (double) $price < (double) $vpfields['min_price'] ) {
				$pmpro_msg  = sprintf(
					__( 'The lowest accepted price is %1$s%2$s. Please enter a new amount.', 'pmpro-variable-procing' ),
					esc_html( $pmpro_currency_symbol ),
					esc_html( $vpfields['min_price'] )
				);
				$pmpro_msgt = 'pmpro_error';
				$continue   = false;
			} elseif ( ! empty( $vpfields['max_price'] ) && ( (double) $price > (double) $vpfields['max_price'] ) ) {
				$pmpro_msg  = sprintf(
					__( 'The highest accepted price is %1$s%2$s. Please enter a new amount.', 'pmpro-variable-pricing' ),
					esc_html( $pmpro_currency_symbol ),
					esc_html( $vpfields['max_price'] )
				);
				$pmpro_msgt = 'pmpro_error';
				$continue   = false;
			}

			// all good!
		}
	}

	return $continue;
}
add_filter( 'pmpro_registration_checks', 'pmprovp_pmpro_registration_checks' );

// save fields in session for PayPal Express/etc
function pmprovp_pmpro_paypalexpress_session_vars() {
	if ( ! empty( $_REQUEST['price'] ) ) {
		$_SESSION['price'] = $_REQUEST['price'];
	} else {
		$_SESSION['price'] = '';
	}
}
add_action( 'pmpro_paypalexpress_session_vars', 'pmprovp_pmpro_paypalexpress_session_vars' );
add_action( 'pmpro_before_send_to_twocheckout', 'pmprovp_pmpro_paypalexpress_session_vars', 10, 2 );

// Load fields from session if available.
function pmprovp_init_load_session_vars() {
	if(function_exists('pmpro_start_session')) {
		pmpro_start_session();
	}

	if ( empty( $_REQUEST['price'] ) && ! empty( $_SESSION['price'] ) ) {
		$_REQUEST['price'] = $_SESSION['price'];
	}
}
add_action( 'init', 'pmprovp_init_load_session_vars', 5 );

/**
 * Register and set variables for JavaScript
 */
function pmprovp_load_scripts() {

	global $gateway, $pmpro_level;

	if ( empty( $pmpro_level ) ) {
		return;
	}

	// get variable pricing info
	$vpfields = pmprovp_get_settings( $pmpro_level->id );

	// no variable pricing? just return
	if ( empty( $vpfields ) || empty( $vpfields['variable_pricing'] ) ) {
		return;
	}

	// Bail if PMPro is not loaded.
	if ( ! function_exists( 'pmpro_getOption' ) ) {
		return;
	}

	if ( empty( $gateway ) ) {
		$gateway = pmpro_getOption( 'gateway' );
	}

	wp_register_script( 'pmprovp', plugins_url( 'javascript/pmpro-variable-pricing.js', __FILE__ ), array( 'jquery' ), '0.4', true );

	wp_localize_script(
		'pmprovp', 'pmprovp', array(
			'settings'        => array(
				'gateway'         => pmpro_getGateway(),
				'gateway_billing' => ( in_array( $gateway, array( 'paypalexpress', 'twocheckout' ) ) !== false ) ? 'false' : 'true',
			),
			'pricing_billing' => ! pmpro_isLevelFree( $pmpro_level ) ? 'true' : 'false',
			'vp_data' => wp_json_encode( $vpfields )
		)
	);
}
add_action( 'wp_enqueue_scripts', 'pmprovp_load_scripts', 5 );

/**
 * Split register/localize and enqueue operation to simplify unhooking JS from plugin if needed
 */
function pmprovp_enqueue_scripts() {

	global $pmpro_level;

	if ( empty( $pmpro_level ) ) {
		return;
	}

	// get variable pricing info
	$vpfields = pmprovp_get_settings( $pmpro_level->id );

	// no variable pricing? just return
	if ( empty( $vpfields ) || empty( $vpfields['variable_pricing'] ) ) {
		return;
	}

	wp_enqueue_script( 'pmprovp' );
}
add_action( 'wp_enqueue_scripts', 'pmprovp_enqueue_scripts', 15 );

/*
Function to add links to the plugin row meta
*/
function pmprovp_plugin_row_meta( $links, $file ) {
	if ( strpos( $file, 'pmpro-variable-pricing.php' ) !== false ) {
		$new_links = array(
			'<a href="' . esc_url( 'https://www.paidmembershipspro.com/add-ons/variable-pricing-add-on/' ) . '" title="' . esc_attr( __( 'View Documentation', 'pmpro' ) ) . '">' . __( 'Docs', 'pmpro' ) . '</a>',
			'<a href="' . esc_url( 'https://www.paidmembershipspro.com/support/' ) . '" title="' . esc_attr( __( 'Visit Customer Support Forum', 'pmpro' ) ) . '">' . __( 'Support', 'pmpro' ) . '</a>',
		);
		$links     = array_merge( $links, $new_links );
	}
	return $links;
}
add_filter( 'plugin_row_meta', 'pmprovp_plugin_row_meta', 10, 2 );

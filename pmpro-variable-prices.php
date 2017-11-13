<?php
/*
Plugin Name: Paid Memberships Pro - Variable Prices
Plugin URI: http://www.paidmembershipspro.com/add-ons/pmpro-variable-prices/
Description: Allow customers to set their own price when checking out for your membership levels.
Version: .4
Author: Stranger Studios
Author URI: http://www.strangerstudios.com
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
	Min Price and Max Price Fields on the edit levels page
*/
//fields on edit page
function pmprovp_pmpro_membership_level_after_other_settings()
{
	global $pmpro_currency_symbol;
	$level_id = intval($_REQUEST['edit']);
	if($level_id > 0)
	{
		$vpfields = get_option( "pmprovp_{$level_id}", array('variable_pricing' => 0, 'min_price' => '', 'max_price' => '', 'no_price'=> false ) );
		$variable_pricing = $vpfields['variable_pricing'];
		$min_price = $vpfields['min_price'];
		$max_price = $vpfields['max_price'];
		$no_price = (bool) $vpfields['no_price'];
	}
	else
	{
		$variable_pricing = 0;
		$min_price = '';
		$max_price = '';
		$no_price = false;
	}
?>
<h3 class="topborder"><?php __('Variable Pricing', 'pmpro-variable-pricing' ); ?></h3>
<p><?php _e('If variable pricing is enabled, users will be able to set their own price. That price will override any initial payment and billing amount values you set on this level. You can set the minimum and maxium price allowed for this level. The set initial payment will be used as the recommended price at chcekout.', 'pmpro-variable-pricing' )?></p>
<table>
<tbody class="form-table">
			<tr>
				<th scope="row" valign="top"><label for="pmprovp_variable_pricing"><?php _e('Enable:', 'pmpro-variable-pricing' ); ?></label></th>
				<td>
					<input type="checkbox" name="variable_pricing" id="pmprovp_variable_pricing" value="1" <?php checked($variable_pricing, "1");?> /> Enable Variable Pricing
				</td>
			</tr>
            <tr>
                <th scope="row" valign="top"><label for="pmprovp_no_price"><?php _e('Blank on checkout page:','pmpro-variable-pricing' ); ?></label></th>
                <td>
                    <input type="checkbox" name="no_price" value="1" id="pmprovp_no_price"<?php checked( true,$no_price ); ?>  />
                </td>
            </tr>
            <tr>
				<th scope="row" valign="top"><label for="pmprovp_min_price"><?php _e('Min Price:', 'pmpro-variable-pricing' ); ?></label></th>
				<td>
					<?php echo $pmpro_currency_symbol?><input type="text" name="min_price" id="pmprovp_min_price" value="<?php echo esc_attr($min_price); ?>" />
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="pmprovp_max_price"><?php _e('Max Price:', 'pmpro-variable-pricing' ); ?></label></th>
				<td>
					<?php echo $pmpro_currency_symbol?><input type="text" id="pmprovp_max_price" name="max_price" value="<?php echo esc_attr($max_price); ?>" />
				</td>
			</tr>
    </tbody>
</table>
<?php
}
add_action("pmpro_membership_level_after_other_settings", "pmprovp_pmpro_membership_level_after_other_settings");

//save level cost text when the level is saved/added
function pmprovp_pmpro_save_membership_level($level_id)
{
	$variable_pricing = intval($_REQUEST['variable_pricing']);
	$min_price = preg_replace("[^0-9\.]", "", $_REQUEST['min_price']);
	$max_price = preg_replace("[^0-9\.]", "", $_REQUEST['max_price']);
	$no_price = isset( $_REQUEST['no_price'] ) ? (bool) $_REQUEST['no_price'] : false;
	
	update_option("pmprovp_" . $level_id, array('variable_pricing' => $variable_pricing, 'min_price' => $min_price, 'max_price' => $max_price, 'no_price' => $no_price ));
}
add_action("pmpro_save_membership_level", "pmprovp_pmpro_save_membership_level");

/*
	Show form at checkout.
*/
//override level cost text on checkout page
function pmprovp_pmpro_level_cost_text($text, $level)
{
	global $pmpro_pages;
	if(is_page($pmpro_pages['checkout']))
	{
		$vpfields = get_option("pmprovp_" . $level->id);
		if(!empty($vpfields) && !empty($vpfields['variable_pricing']))
		{
			$text = "";
		}
	}
	
	return $text;
}
add_filter("pmpro_level_cost_text", "pmprovp_pmpro_level_cost_text", 10, 2);

//show form
function pmprovp_pmpro_checkout_after_level_cost()
{
	global $pmpro_currency_symbol, $pmpro_level, $gateway;
	
	//get variable pricing info
	$vpfields = get_option("pmprovp_" . $pmpro_level->id);
	
	//no variable pricing? just return
	if(empty($vpfields) || empty($vpfields['variable_pricing']))
		return;
	
	//okay, now we're showing the form	
	$min_price = $vpfields['min_price'];
	$max_price = $vpfields['max_price'];
	$no_price = (bool) $vpfields['no_price'];
	
	if(isset($_REQUEST['price'])) {
		$price = preg_replace( "[^0-9\.]", "", $_REQUEST['price'] );
	} else if ( true === $no_price ) {
	    $price = null;
    } else {
		$price = $pmpro_level->initial_payment;
	}
	
	/**
	 * @filter pmpropvp_checkout_price_description - Filter to modify the variable price description text
     * @param string $price_text_description
	 */
	$price_text_description = apply_filters(
	        'pmpropvp_checkout_price_description',
            printf(
                    __( 'Enter a price between %1$s%2$s and %1$s%3$s', 'pmpro-variable-pricing' ),
                    esc_html( $pmpro_currency_symbol ),
                    esc_html( $vpfields['min_price'] ),
                    esc_html( $vpfields['max_price'] )
            )
    );
	
	/**
	 * @filter pmprovp_checkout_price_input_label - Filter to modify the label for the Variable Price input box on the checkout page
     * @param string $price_text
	 */
	$price_text = apply_filters(
	        'pmprovp_checkout_price_input_label',
            sprintf(
                    __( 'Your price: %s', 'pmpro-variable-pricing' ),
                    esc_html( $pmpro_currency_symbol )
            )
    );
?>
<p><?php esc_html_e( $price_text_description );?></p>
<p><?php esc_html_e($price_text ); ?> <input type="text" id="price" name="price" size="10" value="<?php esc_attr_e( $price );?>" /></p>
<script>

</script>
<?php
}
add_action('pmpro_checkout_after_level_cost', 'pmprovp_pmpro_checkout_after_level_cost');

//set price
function pmprovp_pmpro_checkout_level($level)
{
	if(isset($_REQUEST['price']))
		$price = preg_replace("[^0-9\.]", "", $_REQUEST['price']);
	
	if(isset($price))
	{
		$level->initial_payment = $price;
		
		if($level->billing_amount > 0)
			$level->billing_amount = $price;
	}
	
	return $level;
}
add_filter("pmpro_checkout_level", "pmprovp_pmpro_checkout_level");

//check price is between min and max
function pmprovp_pmpro_registration_checks($continue)
{
	//only bother if we are continuing already
	if($continue)
	{
		global $pmpro_currency_symbol, $pmpro_msg, $pmpro_msgt;
		
		//was a price passed in?
		if(isset($_REQUEST['price']))
		{
			//get values
			$level_id = intval($_REQUEST['level']);
			$vpfields = get_option("pmprovp_" . $level_id);						
			
			//make sure this level has variable pricing
			if(empty($vpfields) || empty($vpfields['variable_pricing']))
			{
				$pmpro_msg = __( "Error: You tried to set the price on a level that doesn't have variable pricing. Please try again.", "pmpro-variable-pricing" );
				$pmpro_msgt = "pmmpro_error";
			}
			
			//get price
			$price = preg_replace("[^0-9\.]", "", $_REQUEST['price']);
			
			//check that the price falls between the min and max
			if((double)$price < (double)$vpfields['min_price'])
			{
				$pmpro_msg = sprintf(
				        __( 'The lowest accepted price is %1$s%2$s. Please enter a new amount.', 'pmpro-variable-procing'),
                        esc_html($pmpro_currency_symbol ),
                        esc_html( $vpfields['min_price'] )
                );
				$pmpro_msgt = "pmmpro_error";
				$continue = false;
			}
			elseif((double)$price > (double)$vpfields['max_price'])
			{
				$pmpro_msg = sprintf(
				        __( 'The highest accepted price is %1$s%2$s. Please enter a new amount.', 'pmpro-variable-pricing' ),
                        esc_html($pmpro_currency_symbol ),
                        esc_html( $vpfields['max_price'] )
                );
				$pmpro_msgt = "pmmpro_error";
				$continue = false;
			}
			
			//all good!
		}
	}
	
	return $continue;
}
add_filter("pmpro_registration_checks", "pmprovp_pmpro_registration_checks");

//save fields in session for PayPal Express/etc
function pmprovp_pmpro_paypalexpress_session_vars()
{
	if(!empty($_REQUEST['price']))
		$_SESSION['price'] = $_REQUEST['price'];
	else
		$_SESSION['price'] = "";
}
add_action("pmpro_paypalexpress_session_vars", "pmprovp_pmpro_paypalexpress_session_vars");
add_action("pmpro_before_send_to_twocheckout", "pmprovp_pmpro_paypalexpress_session_vars", 10, 2);

/**
 * Register and set variables for JavaScript
 */
function pmprovp_load_scripts() {
    
    global $gateway;
    
    if ( empty( $gateway ) ) {
        $gateway = pmpro_getOption('gateway' );
    }
    
    wp_register_script( 'pmprovp',plugins_url( 'javascript/pmpro-variable-prices.js', __FILE__ ), array( 'jquery' ), '0.4', true );
    
    wp_localize_script( 'pmprovp', 'pmprovp', array(
        'settings' => array(
            'gateway_billing' => ( in_array($gateway, array("paypalexpress", "twocheckout")) !== false ) ?  "false" : "true" ),
            'pricing_billing' => !pmpro_isLevelFree($pmpro_level ) ? "true" : "false"
        )
    );
}
add_action('wp_enqueue_scripts', 'pmprovp_load_scripts', 5 );

/**
 * Split register/localize and enqueue operation to simplify unhooking JS from plugin if needed
 */
function pmprovp_enqueue_scripts() {
	
	wp_enqueue_script('pmprovp' );
}
add_action( 'wp_enqueue_scripts', 'pmprovp_enqueue_scripts', 15 );

//Load fields from session if available.
function pmprovp_init_load_session_vars()
{
	if(empty($_REQUEST['price']) && !empty($_SESSION['price']))
	{
		$_REQUEST['price'] = $_SESSION['price'];
	}
}
add_action('init', 'pmprovp_init_load_session_vars', 5);


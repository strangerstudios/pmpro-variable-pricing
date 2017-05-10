<?php
/*
Plugin Name: PMPro Variable Prices
Plugin URI: http://www.paidmembershipspro.com/add-ons/pmpro-variable-prices/
Description: Allow customers to set their own price when checking out for your membership levels.
Version: .3
Author: Stranger Studios
Author URI: http://www.strangerstudios.com
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
		$vpfields = get_option("pmprovp_" . $level_id, array('variable_pricing' => 0, 'min_price' => '', 'max_price' => ''));
		$variable_pricing = $vpfields['variable_pricing'];
		$min_price = $vpfields['min_price'];
		$max_price = $vpfields['max_price'];
	}
	else
	{
		$variable_pricing = 0;
		$min_price = '';
		$max_price = '';
	}
?>
<h3 class="topborder">Variable Pricing</h3>
<p>If variable pricing is enabled, users will be able to set their own price. That price will override any initial payment and billing amount values you set on this level. You can set the minimum and maxium price allowed for this level. The set initial payment will be used as the recommended price at chcekout.</p>
<table>
<tbody class="form-table">
	<tr>
		<td>
			<tr>
				<th scope="row" valign="top"><label for="level_cost_text">Enable:</label></th>
				<td>
					<input type="checkbox" name="variable_pricing" value="1" <?php checked($variable_pricing, "1");?> /> Enable Variable Pricing
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="level_cost_text">Min Price:</label></th>
				<td>
					<?php echo $pmpro_currency_symbol?><input type="text" name="min_price" value="<?php echo esc_attr($min_price); ?>" />
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="level_cost_text">Max Price:</label></th>
				<td>
					<?php echo $pmpro_currency_symbol?><input type="text" name="max_price" value="<?php echo esc_attr($max_price); ?>" />
				</td>
			</tr>
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
	
	update_option("pmprovp_" . $level_id, array('variable_pricing' => $variable_pricing, 'min_price' => $min_price, 'max_price' => $max_price));
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
	global $pmpro_currency_symbol, $pmpro_level, $gateway, $pmpro_review;
	
	//get variable pricing info
	$vpfields = get_option("pmprovp_" . $pmpro_level->id);
	
	//no variable pricing? just return
	if(empty($vpfields) || empty($vpfields['variable_pricing']) || $pmpro_review)
		return;
	
	//okay, now we're showing the form	
	$min_price = $vpfields['min_price'];
	$max_price = $vpfields['max_price'];
	
	if(isset($_REQUEST['price']))
		$price = preg_replace("[^0-9\.]", "", $_REQUEST['price']);
	else
		$price = $pmpro_level->initial_payment;		
?>
<p>Enter a price between <?php echo $pmpro_currency_symbol . $vpfields['min_price'];?> and <?php echo $pmpro_currency_symbol . $vpfields['max_price'];?></p>
<p>Your Price: <?php echo $pmpro_currency_symbol;?> <input type="text" id="price" name="price" size="10" value="<?php echo $price;?>" /></p>
<script>
	//some vars for keeping track of whether or not we show billing
	var pmpro_gateway_billing = <?php if(in_array($gateway, array("paypalexpress", "twocheckout")) !== false) echo "false"; else echo "true";?>;
	var pmpro_pricing_billing = <?php if(!pmpro_isLevelFree($pmpro_level)) echo "true"; else echo "false";?>;
	
	//this script will hide show billing fields based on the price set
	jQuery(document).ready(function() {
		//bind check to price field
		var pmprovp_price_timer;
		jQuery('#price').bind('keyup change', function() {
			pmprovp_price_timer = setTimeout(pmprovp_checkForFree, 500);
		});
		
		if(jQuery('input[name=gateway]'))
		{
			jQuery('input[name=gateway]').bind('click', function() {
				pmprovp_price_timer = setTimeout(pmprovp_checkForFree, 500);
			});
		}	
		
		//check when page loads too
		pmprovp_checkForFree();
	});
	
	function pmprovp_checkForFree()
	{
		var price = parseFloat(jQuery('#price').val());
		
		//does the gateway require billing?
		if(jQuery('input[name=gateway]').length)
		{			
			var no_billing_gateways = ['paypalexpress', 'twocheckout'];
			var gateway = jQuery('input[name=gateway]:checked').val();
			if(no_billing_gateways.indexOf(gateway) > -1)
				pmpro_gateway_billing = false;
			else
				pmpro_gateway_billing = true;
		}
				
		//is there a price?
		if(price)
			pmpro_pricing_billing = true;
		else
			pmpro_pricing_billing = false;
				
		//figure out if we should show the billing fields
		if(pmpro_gateway_billing && pmpro_pricing_billing)
		{
			jQuery('#pmpro_billing_address_fields').show();
			jQuery('#pmpro_payment_information_fields').show();
			pmpro_require_billing = true;
		}
		else
		{
			jQuery('#pmpro_billing_address_fields').hide();
			jQuery('#pmpro_payment_information_fields').hide();
			pmpro_require_billing = false;
		}
	}
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
				$pmpro_msg = "Error: You tried to set the price on a level that doesn't have variable pricing. Please try again.";
				$pmpro_msgt = "pmmpro_error";
			}
			
			//get price
			$price = preg_replace("[^0-9\.]", "", $_REQUEST['price']);
			
			//check that the price falls between the min and max
			if((double)$price < (double)$vpfields['min_price'])
			{
				$pmpro_msg = "The lowest accepted price is " . $pmpro_currency_symbol . $vpfields['min_price'] . ". Please enter a new amount.";
				$pmpro_msgt = "pmmpro_error";
				$continue = false;
			}
			elseif((double)$price > (double)$vpfields['max_price'])
			{
				$pmpro_msg = "The highest accepted price is " . $pmpro_currency_symbol . $vpfields['max_price'] . ". Please enter a new amount.";
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

//Load fields from session if available.
function pmprovp_init_load_session_vars($param)
{
	if(empty($_REQUEST['price']) && !empty($_SESSION['price']))
	{
		$_REQUEST['price'] = $_SESSION['price'];
	}
	return $param;
}
add_action('init', 'pmprovp_init_load_session_vars', 5);


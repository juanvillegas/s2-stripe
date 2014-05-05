<?php 

	function generate_map_row( $roles, $plans, $maps = false ){ ?>
		<tr valign="top" class="map_row">
			<td>
				<select name="s2_stripe[map][]" id="" class="s2_stripe_field">
					<?php foreach( $roles as $role_id=>$role_data_arr ): ?>
						<option value="<?php echo $role_id ?>" <?php echo ($maps !== false && $role_id == $maps[0]) ? 'selected' : '' ?> ><?php echo $role_data_arr['name'] ?></option>
					<?php endforeach; ?>
				</select>
			</td>
			<td>
				<select name="s2_stripe[map][]" id="" class="s2_stripe_field">
					<?php foreach( $plans as $plan ): ?>
						<option value="<?php echo $plan->id ?>" <?php echo ($maps !== false && $plan->id == $maps[1]) ? 'selected' : '' ?>><?php echo $plan->name ?></option>
					<?php endforeach; ?>
				</select>
				<a href="#" class="delete">Delete</a>
			</td>
		</tr>
	<?php }

?>

<style>
	.s2_stripe_field {
		width: 350px;
		height: 30px;
	}
</style>
<h2>S2 Stripe - Setup</h2>
<p>Enter your Stripe publishable and secret keys below. The plugin needs them to connect to your Stripe account using the official API.</p>
<form action="" method="post">
	<input type="hidden" name="s2_stripe_options_trigger" value="1">
	<table class="form-table">
		<tbody>
			<tr valign="top">
				<th scope="row">
					<label for="publishable_key">Stripe Publishable Key</label>
				</th>
				<td>
					<input type="text" name="s2_stripe[stripe_pub_key]" class="s2_stripe_field" value="<?php echo isset( $config['stripe_pub_key'] ) ? $config['stripe_pub_key'] : ''; ?>">
					<p class="description">Your Stripe publishable key</p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label for="secret_key">Stripe Secret Key</label>
				</th>
				<td>
					<input type="text" name="s2_stripe[stripe_secret_key]" class="s2_stripe_field" value="<?php echo isset( $config['stripe_secret_key'] ) ? $config['stripe_secret_key'] : ''; ?>">
					<p class="description">Your Stripe secret key</p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label for="s2_proxy">S2 IPN Proxy Key</label>
				</th>
				<td>
					<input type="text" name="s2_stripe[s2_proxy]" class="s2_stripe_field" value="<?php echo isset( $config['s2_proxy'] ) ? $config['s2_proxy'] : ''; ?>">
					<p class="description">Your S2 IPN Proxy. Find it under S2 Member > Paypal Options > Paypal IPN Integration</p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label for="include_styles">Include styles in forms</label>
				</th>
				<td>
					<input type="checkbox" name="s2_stripe[include_styles]" <?php echo isset( $config['include_styles'] ) && $config['include_styles'] == 1 ? 'checked' : ''; ?> value="1">
					<p class="description">Wether or not to include the default form styles</p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label for="s2_admin_email">Administrator email</label>
				</th>
				<td>
					<input type="text" name="s2_stripe[s2_admin_email]" class="s2_stripe_field" value="<?php echo isset( $config['s2_admin_email'] ) ? $config['s2_admin_email'] : ''; ?>">
					<p class="description">We are going to send emails in case of errors to the email above. If its blank, we'll try the site's administrator email.</p>
				</td>
			</tr>
		</tbody>
	</table>
	<p class="submit">
		<input type="submit" name="submit" class="button button-primary" value="S2 Save!">
	</p>
</form>


<?php
	$roles = S2_Stripe::getRoles();

	$plans = S2_Stripe::getStripePlans();
?>
<h2>Plans Map</h2>
<p>Configure your plans. For extended information, consult the documentation.</p>
<form method="post">
	<input type="hidden" name="s2_stripe_map_trigger" value="1">
	<table class="form-table" id="plans_map">
		<tbody>
			<?php
				if( ! isset( $config['map'] ) || ( count( $config['map'] ) == 0 ) ){
					generate_map_row( $roles, $plans );
				}else{
					for( $i = 0; $i < count($config['map'] ); $i = $i + 2 ){
						generate_map_row( $roles, $plans, array( $config['map'][$i], $config['map'][$i+1] ) );
					}
				}
			?>
			<tr valign="top">
				<td><a href="" id="s2_stripe_add_map">Map another</a></td>
			</tr>
		</tbody>
	</table>
	<p class="submit">
		<input type="submit" name="submit" class="button button-primary" value="S2 Save!">
	</p>
</form>

<div id="s2_stripe_test">
	<h2>Test your settings</h2>
	<p>Use the button below to initiate a test request to your Stripe account using the keys provided. If something goes wrong, please verify your settings.</p>
	<a href="#" class="api button button-primary">Test now</a>
	<div id="response_area">
		
	</div>
</div>

<div id="s2_stripe_marketing_message">
	<h2>Need help?</h2>
	<p>The plugin is plug n play: right after installing it and configuring your Stripe account you are ready to start getting payments from your clients using Stripe.</p>
	<p>Anyway, for users that arent techie at all that may be complicated. If you have already checked the docs and asked the public forums, but still couldnt get an answer, stay calm. Im offering paid personalized support, billed hourly.</p>
	<p>To request your shift for support, write an email to <a href="mailto: juan.villgs@gmail.com">juan.villgs@gmail.com</a>. Replies usually take no longer than 1-2 hours.</p>
</div>

<script>

	jQuery(document).ready(function($){
		var map_row_html = $('#plans_map tr').first().html();
		var $plans_map = $('#plans_map');

		function on_map_row_delete(evt){
			$(this).parents('tr').remove();
			evt.preventDefault();
		}

		$('#plans_map .delete').click( on_map_row_delete );

		$('#s2_stripe_add_map').click(function(evt){
			$('<tr class="map_row">' + map_row_html + '</tr>').insertBefore($(this).parents('tr').first());
			$plans_map.find('tr.map_row .delete').last().click( on_map_row_delete ); // selects only the last delete button, which is in the recently created row
			evt.preventDefault();
		});

		var $trigger = $('#s2_stripe_test a.api');
		$trigger.click(function(){
			$trigger.append( '<span> (...)</span>' );
			$.ajax({
				type: 'POST',
				dataType: 'json',
				data: {
					action: 's2_stripe_api_test'
				},
				url: ajaxurl
			}).done(function(response){
				$trigger.find('span').remove();
				if( response.result ){
					$trigger.fadeOut();
				}
				$('#response_area').html( response.data );
			}).fail(function(){
				$trigger.find('span').remove();
				$('#response_area').html( '<p>An error has occurred</p>' );
			});
			return false;
		});

	});
</script>


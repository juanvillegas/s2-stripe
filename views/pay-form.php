<div id="s2_stripe_pay" class="s2_stripe_form_container">
	<form class="s2_stripe_form">
		<input type="hidden" name="s2_level" value="<?php echo base64_encode( $s2_level ) ?>">
		<div class="row">
			<label>First Name*</label>
			<input type="text" name="firstname" maxlength="100" class="validate" data-rules="required">
		</div>
		<div class="row">
			<label>Last Name*</label>
			<input type="text" name="lastname" maxlength="100" class="validate" data-rules="required">
			
		</div>
		<div class="row">
			<label>Email*</label>
			<input type="text" name="email" maxlength="250" class="validate" data-rules="email">
		</div>
		<div class="row">
			<label>Card Number*</label>
			<input type="text" size="20" data-stripe="number" value="" placeholder="xxxx-xxxx-xxxx-xxxx" class="validate" data-rules="required" />
		</div>

		<div class="row">
			<label>CVC*</label>
			<input type="text" size="4" data-stripe="cvc" value="" placeholder="xyz" class="validate" data-rules="required" />
		</div>

		<div class="row cols-2">
			<label>Expiration (MM/YYYY)*</label>
			<select data-stripe="exp-month">
				<?php for( $i = 1; $i <= 12; $i++ ): ?>
				<?php $printable = str_pad("$i", 2, '0', STR_PAD_LEFT); ?>
				<option value="<?php echo $printable ?>"><?php echo $printable ?></option>
				<?php endfor; ?>
			</select>
			<select data-stripe="exp-year">
				<?php $curr_year = date('Y'); ?>
				<?php for( $i = $curr_year; $i <  ($curr_year + 10); $i++ ): ?>
				<option value="<?php echo $i ?>"><?php echo $i ?></option>
				<?php endfor; ?>
			</select>
		</div>
	
		<?php if( $coupons_enabled !== false ): ?>
			<div class="row">
				<label>If you have a discount code, enter it here</label>
				<input type="text" name="coupon" value="" style="width: 263px;">
			</div>
		<?php endif; ?>

		<div class="row s2_stripe_messages">
			<p class="error"></p>
			<p class="success"></p>
		</div>

		<div class="row">
			<a href="#" class="s2_stripe_pay_button"><?php echo $submit_label ?> <img class="s2_stripe_preloader" src="<?php echo S2_Stripe::get_plugin_url() ?>assets/images/preloader.gif" alt=""></a>
		</div>
	</form>
</div><!-- /#s2_stripe_form_container -->

<script>
	
	jQuery(document).ready(function($){

		Stripe.setPublishableKey("<?php echo S2_Stripe_DataManager::get_stripe_keys('p') ?>");

		var halt_submit = false;

		var $container = $('#s2_stripe_pay');
		var $pay_form = $container.children('form').first();
		var $pay_button = $container.find('.s2_stripe_pay_button').first();
		var $preloader = $container.find('.s2_stripe_preloader').first();
		var $messages = $container.find('.s2_stripe_messages').first();

		$pay_button.click(function() {
			if( halt_submit ){
				return false;
			}

			$messages.find('p').hide();

			var data_ok = $pay_form.validate();

			if( ! data_ok ){
				return false;
			}

			$preloader.css('visibility', 'visible');

			// Disable the submit button to prevent repeated clicks
			halt_submit = true;

			Stripe.card.createToken($pay_form, stripeResponseHandler);

			// Prevent the form from submitting with the default action
			return false;
		});


		var stripeResponseHandler = function(status, response) {
			if( response.error ){
				$preloader.css('visibility', 'hidden');
				$messages.find( '.error' ).first().html(response.error.message).slideDown();
				halt_submit = false; // restore submit
			}else{
				// token contains id, last4, and card type
				var token = response.id;
				// Insert the token into the form so it gets submitted to the server
				$pay_form.append($('<input type="hidden" name="stripeToken" />').val(token));
				var request = $.ajax({
					url: '<?php echo admin_url() ?>admin-ajax.php',
					type: "POST",
					data: {
						action: 's2_stripe_ajax_pay',
						formdata: $pay_form.serializeArray() 
					},
					dataType: "json"
				});

				request.done(function( response ) {
					$preloader.css('visibility', 'hidden');
					if( response.result ){
						// success
						$messages.children('.success').first().html(response.data).show();
					}else{
						// failed
						$messages.children('.error').first().html(response.data).show();
						halt_submit = false;
					}
				});

				request.fail(function( jqXHR, textStatus ) {
					$preloader.css('visibility', 'hidden');
					$messages.children('.error').first().html('There was an unexpected error. Please, try again in a few minutes.').show();
					halt_submit = false; // renables submit button
				});
			}
		};
	});

</script>

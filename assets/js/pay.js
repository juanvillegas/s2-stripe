jQuery(document).ready(function($) {

	Stripe.setPublishableKey('<?php echo S2_STRIPE_PUBLISHABLE ?>');

	$('#s2_stripe_pay_button').click(function() {
		if( halt_submit ){
			return false;
		}

		$('#s2_stripe_messages *').hide();

		var data_ok = do_validate('#s2_stripe_form');

		if( ! data_ok ){
			return false;
		}

		// go to the skieeeees
		$('#s2_stripe_pay_button .s2_stripe_preloader').css('visibility', 'visible');

		var $form = $('#s2_stripe_form');

		// Disable the submit button to prevent repeated clicks
		halt_submit = true;

		Stripe.card.createToken($form, stripeResponseHandler);

		// Prevent the form from submitting with the default action
		return false;
	});
	
}); // end ready

var stripeResponseHandler = function(status, response) {
	var $form = jQuery('#s2_stripe_form');

	if( response.error ){
		jQuery('#s2_stripe_pay_button .s2_stripe_preloader').css('visibility', 'hidden');
		jQuery('#s2_stripe_messages .error').html(response.error.message).slideDown();
		halt_submit = false; // restore submit
	}else{
		// token contains id, last4, and card type
		var token = response.id;
		// Insert the token into the form so it gets submitted to the server
		$form.append(jQuery('<input type="hidden" name="stripeToken" />').val(token));
		var request = jQuery.ajax({
			url: '<?php echo admin_url() ?>admin-ajax.php',
			type: "POST",
			data: {
				action: 's2_stripe_ajax_process_payment',
				formdata: $form.serializeArray() 
			},
			dataType: "json"
		});

		request.done(function( response ) {
			console.log(response);
			jQuery('#s2_stripe_pay_button .s2_stripe_preloader').css('visibility', 'hidden');
			if( response.result ){
				// success
				jQuery('#s2_stripe_messages .success').html(response.data).show();
			}else{
				// failed
				jQuery('#s2_stripe_messages .error').html(response.data).show();
				halt_submit = false;
			}
		});

		request.fail(function( jqXHR, textStatus ) {
			jQuery('#s2_stripe_pay_button .s2_stripe_preloader').css('visibility', 'hidden');
			jQuery('#s2_stripe_messages .error').html('There was an unexpected error. Please, try again in a few minutes.').show();
			halt_submit = false; // renables submit button
		});
	}
};

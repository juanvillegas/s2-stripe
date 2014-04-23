<?php

	class S2_Stripe_Mail{

		static function notify_s2_pay_failed(){

			$parse = parse_url( site_url() );

			$headers = array();
			$headers[] = 'From: '. get_bloginfo( 'name' ) .' <noreply@'. $parse['host'] .'>';

			$message = 'A new user subscribed on your site, but we couldnt create a profile for him. The IPN system failed.<br />';
			$message .= 'The new user details are copied below. An account for this user should be created manually, or a refund should be provided as the Stripe payment was processed.<br /><br />';
			$message .= 'Firstname:' . $data['firstname'];
			$message .= 'Lastname:' . $data['lastname'];
			$message .= 'Email:' . $data['email'];
			$message .= 'Stripe Plan:' . $data['stripe_level'];
			$message .= 'S2 Level:' . $data['s2_level'];

			wp_mail( get_option('admin_email') , 'URGENT: S2 IPN Failed', $message, $headers );
		}

	}

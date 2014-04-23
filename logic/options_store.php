<?php

	$current = S2_Stripe_DataManager::get_options();
	if( isset( $_POST['s2_stripe_options_trigger'] ) ){	
		$updated = $_POST['s2_stripe'];

		if( ! isset( $updated['include_styles'] ) ){
			$updated['include_styles'] = 0;
		}
		
		if( ! empty( $current ) ){
			$updated = array_merge( $current, $updated );
		}

		S2_Stripe_DataManager::set_options( $updated );

		$config = $updated;
	}

	if( isset( $_POST['s2_stripe_map_trigger'] ) ){
		$updated = $_POST['s2_stripe'];
		
		if( ! empty( $current ) ){
			$updated = array_merge( $current, $updated );
		}

		S2_Stripe_DataManager::set_options( $updated );

		$config = $updated;
	}

	if( ! isset( $config ) ){
		$config = $current;
	}

?>

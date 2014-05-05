<?php
/**
 * Plugin Name: S2 Member - Stripe Gateway
 * Plugin URI: http://juanvillegas.ws
 * Description: Integrates Stripe into your S2 Member installation
 * Version: 1.1.1
 * Author: Juan Villegas
 * Author URI: http://juanvillegas.ws
 * License: MIT
 */


define('STRIPE_PHP_FOLDER_NAME', 'stripe-php-1.11.0');
DEFINE('S2_STRIPE_REPO_USERNAME', 'juanvillegas');
DEFINE('S2_STRIPE_REPO_NAME', 's2-stripe');

// Logic for the uploader
include('logic/updater.php');
if ( is_admin() ) {
    new CoolPluginUpdater( __FILE__, S2_STRIPE_REPO_USERNAME, S2_STRIPE_REPO_NAME );
}



/* ====================================
s2_stripe_serial => array(
	'stripe_pub_key' => 123,
	'stripe_secret_key' => 123,
	's2_proxy' => 123456,
	's2_admin_email' => 123@123.com
)
===================================== */
class S2_Stripe_Messages {

	static $messages = array(
		'common.error' => 'Something went wrong. Please try again!',
		'common.requires_login' => 'User is not logged in.',
		'common.unrecognized' => 'An unrecognized error has occurred.',
		'stripe.error.request' => 'A fatal error occurred. Please try again later or contact an administrator.',
		'stripe.error.stripe' => 'There was a problem contacting the card processor. Please, try again in a few minutes',
		'stripe.error.card' => 'There is a problem with your card. Are you sure it has available funds?',
		'stripe.error.fatal' => 'Something went wrong and the transaction couldnt be processed. Try again later.',
		'ipn.error.ping' => 'An internal error has occured, but your payment was processed. An administrator will contact you in the next 24 hours to set up your account.',
		'ipn.error.charged_but_error' => 'You card was charged, but the registration process failed. An administrator we\'ll get in touch as soon as possible to complete your registration manually',
		'ipn.success.account_upgraded' => 'Success! Your account has been successfully upgraded!',
	);

	static function get( $key ){
		if( isset( self::$messages[$key] ) ){
			return self::$messages[$key];
		}else{
			return false;
		}
	}

}

class S2_Stripe_DataManager {

	static function get_options(){
		return unserialize( get_option( 's2_stripe_serial' ) );
	}

	static function set_options( $data ){
		update_option( 's2_stripe_serial', serialize( $data ) );
	}

	static function getPlansMap(){
		$options = S2_Stripe_DataManager::get_options();
		if( isset( $options['map'] ) ){
			return $options['map'];
		}else{
			return false;
		}
	}

	static function log( $data ){
		$to_log = "========================================\r\n";
		$to_log .= "New log @ " . date('Y-m-d h:m:s') . "\r\n";
		$to_log .= $data . "\r\n\r\n";

		$bytes = file_put_contents(S2_Stripe::get_plugin_path() . 'logs/log.txt', $to_log, FILE_APPEND);

		if( $bytes === false ){
			return false;
		}else{
			return true;
		}
	}

	static function get_stripe_keys( $type = '' ){
		$options = S2_Stripe_DataManager::get_options();
		if( $type == '' ){
			return array( 
				'p' => $options['stripe_pub_key'], 
				's' => $options['stripe_secret_key'] 
			);
		}else if( $type == 's' ){
			return $options['stripe_secret_key'];
		}else{
			return $options['stripe_pub_key'];
		}
	}

	static function get_ipn_proxy(){
		$options = S2_Stripe_DataManager::get_options();
		return $options['s2_proxy'];
	}

	static function include_css(){
		$options = S2_Stripe_DataManager::get_options();
		return isset($options['include_styles']) && $options['include_styles'] == 1;
	}

	static function get_best_email(){
		$options = S2_Stripe_DataManager::get_options();
		if( isset( $options['s2_admin_email'] ) ){
			return $options['s2_admin_email'];
		}else{
			return get_option( 'admin_email' );
		}
	}

}

class S2_Stripe {

	private $initialized = false;

	/**
	Retrieves true if the user registered using Stripe, false otherwise
	* */
	static function isStripe(){
		$fields = json_decode( S2MEMBER_CURRENT_USER_FIELDS, true );

		if( $fields['subscr_gateway'] == 'stripe' ){
			return true;
		}else{
			return false;
		}
	}

	static function get_stripe_php_path(){
		return S2_Stripe::get_plugin_path() . 'lib/'. STRIPE_PHP_FOLDER_NAME .'/lib/Stripe.php';
	}

	static function get_plugin_path(){
		return plugin_dir_path( __FILE__ );
	}

	static function get_plugin_url(){
		return plugins_url( '', __FILE__ ) . '/';
	}

	function initialize(){
		if( $this->initialized ){
			return true;
		}

		$this->register_menues();
		$this->register_shortcodes();

		add_action('init', array( $this, 's2_stripe_register_scripts' ) );
		add_action('wp_footer', array( $this, 's2_stripe_print_scripts' ) );

		// give me AJAX
		add_action( 'wp_ajax_s2_stripe_ajax_pay', array( $this, 's2_stripe_ajax_pay_handler' ) );
		add_action( 'wp_ajax_nopriv_s2_stripe_ajax_pay', array( $this, 's2_stripe_ajax_pay_handler' ) );
		add_action( 'wp_ajax_s2_stripe_ajax_upgrade', array( $this, 's2_stripe_ajax_upgrade_handler' ) );
		add_action( 'wp_ajax_nopriv_s2_stripe_ajax_upgrade', array( $this, 's2_stripe_ajax_upgrade_handler' ) );
		add_action( 'wp_ajax_s2_stripe_api_test', array( $this, 's2_stripe_api_test_handler' ) );
		add_action( 'wp_ajax_s2_stripe_api_call', array( $this, 's2_stripe_api_call_handler' ) );

		
		add_action('init', array( $this, 's2_stripe_webhook' ) );


		$this->initialized = true;
	}


	function s2_stripe_print_scripts(){
		global $s2_stripe_pay_enable;

		if ( $s2_stripe_pay_enable ){
			wp_print_scripts( 's2_stripe_js' );
			wp_print_scripts( 's2_stripe_validate' );

			if( S2_Stripe_DataManager::include_css() ){
				wp_print_styles( 's2_stripe_form_css' );
			}
		}
	}


	function s2_stripe_register_scripts(){
		wp_register_script( 's2_stripe_js', 'https://js.stripe.com/v2/' );
		wp_register_script( 's2_stripe_validate', S2_Stripe::get_plugin_url() . 'assets/js/validate.js' );

		wp_register_style( 's2_stripe_form_css', S2_Stripe::get_plugin_url() . 'assets/css/forms.css' );
	}


	function register_menues(){
		add_action( 'admin_menu', array( $this, 'build_menues') );
	}


	function build_menues(){
		add_options_page( 'S2 Stripe', 'S2 Stripe', 'manage_options', 's2-stripe', array( $this, 'build_options_view' ) );
	}


	function build_options_view() {
		if ( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
		include( dirname( __FILE__ ) . '/logic/options_store.php' );
		include( dirname(__FILE__) . '/views/options.php' );
	}


	function register_shortcodes(){
		add_shortcode( 's2_stripe_pay', array( $this, 'build_s2_stripe_pay' ) );
		add_shortcode( 's2_stripe_upgrade', array( $this, 'build_s2_stripe_upgrade' ) );
		//add_shortcode( 's2_stripe_generate_upgrade_url', 'do_s2_stripe_generate_upgrade_url' );
	}


	function build_s2_stripe_pay( $atts, $content ){
		extract( shortcode_atts( array(
			's2_level' => 0,
			'submit_label' => 'Submit',
			'coupons_enabled' => false // false => no coupons, true => enable coupons
		), $atts ) );

		// basic tests before proceeding..
		if( $s2_level === 0 ){
			return 'Error: wrong level ID';
		}

		global $s2_stripe_pay_enable;
		$s2_stripe_pay_enable = true;

		ob_start();

		include( dirname( __FILE__ ) . '/views/pay-form.php' );

		$buffy = ob_get_contents();
		ob_end_clean();

		return $buffy;
	}

	function build_s2_stripe_upgrade( $atts, $content ){
		extract( shortcode_atts( array(
			'label' => 'Upgrade/Downgrade', // label for the submit button in the form
			'coupons_enabled' => false // false => no coupons, true => enable coupons
		), $atts ) );

		// to upgrade, user must be logged in!
		if( ! S2MEMBER_CURRENT_USER_IS_LOGGED_IN ){
			return '';
		}

		// get plans-map
		global $wp_roles;
		$roles = $wp_roles->get_names();

		$map = S2_Stripe_DataManager::getPlansMap();
		foreach( $roles as $k=>$v ){
			if( ! in_array( $k, $map ) ){
				unset( $roles[$k] );
			}
		}

		$user = new WP_User( S2MEMBER_CURRENT_USER_ID );
		$current_role = $user->roles[0];
		unset( $roles[$current_role] );

		foreach( $roles as $k=>$v ){
			$roles[$k] = self::getRoleNicename( $k );
		}

		global $s2_stripe_pay_enable;
		$s2_stripe_pay_enable = true;

		ob_start();

		include( dirname( __FILE__ ) . '/views/upgrade-form.php' );

		$buffy = ob_get_contents();
		ob_end_clean();

		return $buffy;
	}


	/**
	 * ajax handler for api testing
	 *
	 */
	function s2_stripe_api_call_handler(){
		$data = $post;
		if( ! isset( $data['procedure'] ) ){
			echo json_encode( array( 'result' => false, 'data' => 'Invalid request: missing "procedure" key.' ) );
			exit; 
		}

		include( S2_Stripe::get_stripe_php_path() );
		$keys = S2_Stripe_DataManager::get_stripe_keys();
		Stripe::setApiKey( $keys['s'] );
			
		$procedure = $data['procedure'];
		if( $procedure == 'getPlans' ){
			$allPlans = S2_Stripe::getStripePlans();
			echo json_encode( array( 'result' => false, 'data' => $allPlans ) );
			exit; 
		}else{
			echo json_encode( array( 'result' => false, 'data' => "Procedure $procedure is not supported at this time" ) );
			exit; 
		}

	}
	/**
	 * ajax handler for api testing in options page
	 *
	 */
	function s2_stripe_api_test_handler(){
		include( S2_Stripe::get_stripe_php_path() );
		$keys = S2_Stripe_DataManager::get_stripe_keys();
		Stripe::setApiKey( $keys['s'] );

		try{
			$response = Stripe_Account::retrieve();
			$result_data = '<p>Communication OK!</p>';
			$result_data .= '<p>Ready to make live charges? '. ($response->charge_enabled ? 'Yes' : 'No') .'</p>';
			$result_data .= '<p>Ready to move money from your account? '. ( $response->transfer_enabled ? 'Yes' : 'No') .'</p>';
			$result = true;
		}catch( Stripe_AuthenticationError $e ){
			$body = $e->getJsonBody();
			$result_data = '<p>' . $body['error']['message'] . '</p>';
			$result = false;
		}catch( Exception $e2 ){
			$result_data = '<p>Generic error in the communication</p>';
			$result = false;
		}

		echo json_encode( array( 'result' => $result, 'data' => $result_data ) );
		exit;

	}

	/**
	 * ajax handler for new registrations
	 *
	 */
	function s2_stripe_ajax_pay_handler(){
		
		$formdata = $_POST['formdata'];

		// build data as $k=>$v (comes serializeArray'ed..)
		$data = array();
		foreach( $formdata as $value ){
			$data[$value['name']] = $value['value'];
		}

		// get associated Stripe plan:
		$role = base64_decode( $data['s2_level'] );
		$stripePlan = S2_Stripe::getStripePlanForRole($role);
		if( $stripePlan == false ){
			$result_data = 'A fatal error occured. Please, contact an administrator or try again later';
			$result = false;
		}
		
		include( S2_Stripe::get_stripe_php_path() );
		$keys = S2_Stripe_DataManager::get_stripe_keys();
		Stripe::setApiKey( $keys['s'] );

		$token = $data['stripeToken'];

		$result = false;
		$result_data = '';

		// create customer
		try {
			$customer_data = array(
				'card' => $token,
				'metadata' => array(
					'firstname' => $data['firstname'],
					'lastname' => $data['lastname'],
					's2_plan_nicename' => self::getRoleNicename($role),
					's2_plan' => $role
				),
				'plan' => $stripePlan,
				'email' => $data['email']
			);

			// if there is a coupon, apply it
			if( ! empty($data['coupon'] ) ){
				$coupon = $data['coupon'];
				$customer_data['coupon'] = $coupon;

				//$cu = Stripe_Customer::retrieve( $customer->id );
				//$cu->coupon = $coupon;
				//$cu->save();
			}

			$customer = Stripe_Customer::create( $customer_data );
			
			$unique = $customer->id;

			// payment was correct, proceed to create user in s2
			$fields = array(
				'subscr_id' => $unique,
				'txn_type' => 'subscr_signup',
				'txn_id' => $unique . '-stripe',
				'payer_email' => $data['email'],
				'first_name' => $data['firstname'],
				'last_name' => $data['lastname'],
				'modify' => 0,
				'item_number' => S2_Stripe::getRoleLevel( $role ),
				'item_name' => self::getRoleNicename($role),
				'currency_code' => 'USD'
			);

			$fields_string = http_build_query( $fields );

			$ping_result = $this->ipn_ping( $fields_string, $data );
			if( ! $ping_result ){
				// if curl failed, we notify the client and mail admin to manually create the profile..
				S2_Stripe_Mail::notify_s2_pay_failed();
				
				$result_data = 'You card was charged, but the registration process failed. An administrator we\'ll get in touch as soon as possible to complete your registration manually';
				$result = false;
			}else{
				$result = true;
				$result_data = 'Your account has been successfully created. Check your inbox!';
			}
		}catch (Stripe_CardError $e) {
			$body = $e->getJsonBody();
  			$err = $body['error'];
			$result_data = $err['message'];
		}catch (Stripe_Error $e) {
			$body = $e->getJsonBody();
  			$err = $body['error'];
			$result_data = $err['message'];
		} catch (Exception $e) {
			$body = $e->getJsonBody();
  			$err = $body['error'];
  			$result_data = $err['message'];
		}

		echo json_encode( array( 'result' => $result, 'data' => $result_data ) );
		exit;
	}


	/**
	 * ajax handler for upgrades
	 *
	 */
	function s2_stripe_ajax_upgrade_handler(){
		// to upgrade, user must be logged in!
		if( ! S2MEMBER_CURRENT_USER_IS_LOGGED_IN ){
			echo json_encode( array( 'result' => false, 'data' => S2_Stripe_Messages::get('common.requires_login' ) ) );
			exit;
		}

		$formdata = $_POST['formdata'];
		// build data as $k=>$v (comes serializeArray'ed..)
		$data = array();
		foreach( $formdata as $value ){
			$data[$value['name']] = $value['value'];
		}

		// get the target role
		$target_role = $data['target_plan'];
		$target_role_level = S2_Stripe::getRoleLevel($target_role);
		$target_plan = S2_Stripe::getStripePlanForRole( $target_role );
		if( $target_plan == false ){
			echo json_encode( array( 'result' => false, 'data' => S2_Stripe_Messages::get('common.error') ) );
			exit;
		}


		$s2Fields = json_decode(S2MEMBER_CURRENT_USER_FIELDS, true);
		$stripe_customer_id = $s2Fields['subscr_id'];

		include( S2_Stripe::get_stripe_php_path() );
		$keys = S2_Stripe_DataManager::get_stripe_keys();
		Stripe::setApiKey( $keys['s'] );

		$token = $data['stripeToken'];

		try {
			$cu = Stripe_Customer::retrieve( $stripe_customer_id );

			// if the user used a new card, add it and make it the default
			if( $token != '' ){
				$new_card_id = $cu->cards->create(array("card" => $token));
				// make it default
				$cu->default_card = $new_card_id;
				$cu->save();
			}

		    $subscription = $cu->subscriptions->all( array( 'count' => 1 ) ); // get latest subscription from Stripe
		    $subscription = $subscription['data'][0];

		    $current_plan_object = $subscription->plan;

		    $target_plan_object = Stripe_Plan::retrieve( $target_plan );

		    $subscription->plan = $target_plan_object->id;
		    $subscription->save();

			// update metadata with new plan
		    $cu->metadata->s2_plan_nicename = self::getRoleNicename($target_role);
		    $cu->metadata->s2_plan = $target_role;
			$cu->save();
			
			$new_plan_nicename = $target_plan_object->name;

			// payment was correct, proceed to upgrade user in s2
			$fields = array(
				'subscr_id' => urlencode($stripe_customer_id),
				'txn_type' => urlencode('subscr_modify'),
				'txn_id' => urlencode($unique) . '-stripe-' . time(),
				'payer_email' => $s2Fields['email'],
				'first_name' => urlencode($s2Fields['first_name']),
				'last_name' => urlencode($s2Fields['last_name']),
				'modify' => 1,
				'item_number' => $target_role_level,
				'item_name' => self::getRoleNicename($target_role),
				'currency_code' => urlencode('USD')
			);
			$fields_string = http_build_query( $fields );

			$ping_result = $this->ipn_ping( $fields_string, $data );
			if( ! $ping_result ){
				// if curl failed, we notify the client and mail admin to manually create the profile..
				S2_Stripe_Mail::notify_s2_pay_failed();
				
				echo json_encode( array( 'result' => false, 'data' => S2_Stripe_Messages::get('ipn.error.charged_but_error') ) );
				exit;
			}else{
				echo json_encode( array( 'result' => true, 'data' => S2_Stripe_Messages::get('ipn.success.account_upgraded') ) );
				exit;
			}

		}catch (Stripe_InvalidRequestError $e) {
			echo json_encode( array( 'result' => false, 'data' => S2_Stripe_Messages::get('stripe.error.request') ) );
			exit;

		}catch (Stripe_Error $e) {
			echo json_encode( array( 'result' => false, 'data' => S2_Stripe_Messages::get('stripe.error.stripe') ) );
			exit;

		}catch (Exception $e) {
			echo json_encode( array( 'result' => false, 'data' => S2_Stripe_Message::get('common.error') ) );
			exit;
		}
		echo json_encode( array( 'result' => false, 'data' => S2_Stripe_Message::get('common.unrecognized') ) );
		exit;
	}



	/**
		Sends a IPN ping notification to the S2 proxy gateway
	*/

	function ipn_ping( $query, $data ){
		$parse = parse_url( site_url() );

		// TODO: fix the 'custom' param in the line below..
		$gateway = site_url() . '?s2member_paypal_notify=1&s2member_paypal_proxy=stripe&s2member_paypal_proxy_verification='. S2_Stripe_DataManager::get_ipn_proxy() .'&custom=kubicastudio.com&' . $query;
	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_URL, $gateway );
	    curl_setopt($ch, CURLOPT_POST, true);
	    curl_setopt($ch, CURLOPT_POSTFIELDS, array());
	    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    $result = curl_exec( $ch );
	    $code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		
		if( $code = 200 ){
			return true;
		}else{
			return false;
		}
	}// end ipn_ping


	/**
		Listens for notifications from your Stripe system. Everytime a new event (new user, subscription cancellation, etc) is dispatched, Stripe
		will ping us at site_url()?s2_stripe_listener=1. Thus, we'll intercept that request and act acodingly.
	*/
	function s2_stripe_webhook() {
	 	
		if(isset($_GET['s2_stripe_listener']) && $_GET['s2_stripe_listener'] == '1') {
	 		include( S2_Stripe::get_stripe_php_path() );
			$keys = S2_Stripe_DataManager::get_stripe_keys();
			Stripe::setApiKey( $keys['s'] );

			// Retrieve the request's body and parse it as JSON
			$body = file_get_contents('php://input');

			// dump it
			S2_Stripe_DataManager::log( $body );

			$event_json = json_decode($body);
			if( $event_json != NULL ):
				if( $event_json->type == 'customer.subscription.deleted' ){
					// safely request event data
					try {
						$event_obj = Stripe_Event::retrieve($event_json->id );

						$customer_id = $event_obj->data->object->customer;
						$plan_object = $event_obj->data->object->plan;
						/*$users = get_users(
							array(
								'meta_key' => 'tw_s2member_subscr_id',
								'meta_value' => $customer_id
							)
						);*/

						// proceed to cancel user in s2
						$fields = array(
							'subscr_id' => $customer_id,
							'txn_type' => 'subscr_cancel',
							'txn_id' => 'stripe-cancel-'. time(),
							'item_number' => $plan_object->id, // TODO: i think we have to use the local s2 membership level here
							'item_name' => $plan_object->name // TODO: we have to test it without this parameter
						);

						$fields_string = http_build_query($fields);
						
						$this->ipn_ping( $fields_string, array() );
					} catch( Exception $e ){
						// ok, at least we tried :(
						// dump it
						S2_Stripe_DataManager::log( $e->getTraceAsString() );
					}
				}
			endif;

	 		header("HTTP/1.0 200 OK");
	 		exit;
		}
	}// end s2_stripe_webhook


	// Helper Methods

	// returns all Stripe plans
	static function getStripePlans(){
		include( S2_Stripe::get_stripe_php_path() );
		$keys = S2_Stripe_DataManager::get_stripe_keys();
		Stripe::setApiKey( $keys['s'] );

		$allPlans = array();
		$startingAfter = false;
		do {
			if( $startingAfter ){
				$response = Stripe_Plan::all(array( 'limit' => 100, 'starting_after' => $starting_after ));
			}else{
				$response = Stripe_Plan::all(array( 'limit' => 100 ));
			}
			$plans = $response['data'];
			$allPlans = array_merge( $allPlans, $plans );
			$startingAfter = $plans[count($plans) - 1]->id;
		} while( $response['has_more'] == true );
		return $allPlans;
	}

	static function getRoles(){
		$roles = get_editable_roles();
		unset( $roles['administrator'] ); // we wont allow mappings to admin

		return $roles;
	}

	// return the Stripe plan ID associated to $role, or false if none is found
	static function getStripePlanForRole( $role ){
		$map = S2_Stripe_DataManager::getPlansMap();
		for( $i = 0; $i < count( $map ); $i = $i + 2 ){
			if( $map[$i] == $role ){
				return $map[$i+1];
			}
		}
		return false;
	}

	// Gets the 'item_number' value for a given $role
	// returns 0..4 if the $role is valid, false otherwise
	static function getRoleLevel( $role ){
		if( strpos( $role, 's2member_level') === 0 ){
			return substr( $role, -1 );
		}elseif( $role == 'subscriber' ){
			return 0;
		}else{
			return false;
		}
	}

	static function getRoleNicename( $role ){
		$roleLevel = self::getRoleLevel( $role );
		if( $roleLevel !== false ){
			return constant('S2MEMBER_LEVEL' . $roleLevel .'_LABEL');
		}else{
			return false;
		}
	}


} // end S2_Stripe class


// run!
$s2_stripe_object = new S2_Stripe();
$s2_stripe_object->initialize();



/*array(13) {
  ["id"]=>
  int(3)
  ["ip"]=>
  string(14) "190.211.205.92"
  ["reg_ip"]=>
  string(14) "190.211.205.92"
  ["email"]=>
  string(24) "juan@sheologydigital.com"
  ["login"]=>
  string(7) "juanchi"
  ["first_name"]=>
  string(4) "juan"
  ["last_name"]=>
  string(8) "villegas"
  ["display_name"]=>
  string(13) "juan villegas"
  ["subscr_id"]=>
  string(18) "cus_3r1W3sqByzd8J7"
  ["subscr_or_wp_id"]=>
  string(18) "cus_3r1W3sqByzd8J7"
  ["subscr_gateway"]=>
  string(6) "stripe"
  ["custom"]=>
  string(16) "kubicastudio.com"
  [0]=>
  bool(false)
}
*/

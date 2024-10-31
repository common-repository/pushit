<?php
require_once('../../../wp-config.php' );
require_once('pushit.php');
nocache_headers();
require_once ('phoneparser.class.php');

error_reporting(0);

if (isset($_POST['precheck']) && ($_POST['precheck'] == 'true')){
	// we have to check if user's mobile found in the database and if we have this code in the usermeta
	list($user_id, $code) = pushit_mobilforward_check_input_data();
	if ($user_id === false){
		echo 'status:reject; msg:'.$code;
	} else {
		echo 'status:ok';
	}
} else {
	list($user_id, $code) = pushit_mobilforward_check_input_data();
	if ($user_id === false){
		echo $code;
	} else {
		$user_data = get_userdata($user_id);
		$new_pass = wp_generate_password( 5, false ); // Generate something random for a key...
		wp_set_password( $new_pass, $user_data->ID );
		
		do_action( 'password_reset', $user_data );

		$msg = build_template( get_option( 'pushit_reminder_format' ), array(
			'[BLOG_NAME]' => get_bloginfo('name'),
			'[BLOG_URL]' => get_option('siteurl') . ' ',
			'[NAME]' => coalesce( $user_data->display_name, $user_data->login ),
			'[PASS]' => $new_pass
			)
		);
		delete_usermeta($user_data->ID, 'pushit_new_pass_code');
		echo $msg;
	}
}

function pushit_mobilforward_check_input_data (){
	$parser = new PhoneParser($_POST['msisdn']);
	if ($parser->parse()){
		$id = get_user_id_by_phone($parser->country_code, $parser->number);
		if (empty($id)){
			return array(false,'The phone not found.');
			
		}
		$received_code = explode(get_option('pushit_mobilforward_order_key'), $_POST['ordertext']);

		if (($received_code === false) || (count($received_code) != 2)) {
			return array(false,'Site was not configured properly.');
		}
		$received_code = $received_code[1];
		$stored_code = get_usermeta($id, 'pushit_new_pass_code');
		if ($stored_code == $received_code){ // we found that user made request to restore password
			return array($id, $stored_code);
		} else {
			return array(false,'Restore password key was not found. Please try again.');
		}
	} else {
		return array(false,'Phone number is invalid.');
	}
}

?>
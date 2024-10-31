<?php
/*
Plugin Name: Pushit

Plugin URI: http://www.handshake.se/pushit/

Description: Sign in, registration and password retrieval functionalty via AJAX &amp; SMS. Please specify your mobilstart.se credentials on the <a href="options-general.php?page=pushit">Settings page</a> to make plugin work properly. Be sure to make worldwritable files: <strong>/wp-login.php, /wp-admin/includes/template.php, /wp-admin/user-edit.php, /wp-admin/user-new.php </strong> before activating pushit plugin and before deactivating it. <a href="/wp-content/plugins/pushit/check_permissions.php" target="_blank">Click here to check permissions</a>. It must be done! Also do not forget make backup of your blog\'s files and database.

Author: mobilstart.se

Version: 0.3

*/

require_once(dirname(__FILE__)."/functions.php");
include_once(dirname(__FILE__)."/beetag.php");



register_activation_hook( __FILE__, 'pushit_init' );
register_deactivation_hook( __FILE__, 'pushit_deactivate' );

add_action( 'init', 'pushit_on_init');
add_action( 'wp_head',    'pushit_head' );
add_action( 'admin_menu', 'pushit_add_optionsmenu' );
add_action( 'the_content', 'pushit_pushit_control' );
add_action( 'login_head', 'pushit_login_head');
add_action( 'login_form', 'pushit_on_login_form',1,-1);
//add_action( 'login_messages', 'pushit_on_login_messages');
add_action( 'wp_authenticate', 'pushit_on_wp_authenticate');

add_action( 'lostpassword_form', 'pushit_block_register_form');
add_action( 'lostpassword_post', 'pushit_on_lostpassword_post', 2, 1); // added by Konstantin Maximchik

// action register_post is for validate user's mobile
add_action( 'register_head', 'pushit_head');
add_action( 'register_post', 'check_mobile_phone_number', 1, 3); // added by Konstantin Maximchik
add_action( 'user_register', 'pushit_on_user_register', 2); // added by Konstantin Maximchik
add_action( 'register_form', 'pushit_add_fields_to_register_form', 0, -1);

// in admin area
add_action( 'edit_user_profile_update', 'pushit_user_profile_update', 1); // added by Konstantin Maximchik
add_action( 'personal_options_update', 'pushit_user_profile_update', 1);// added by Konstantin Maximchik

add_action( 'profile_personal_options', 'pushit_on_profile_personal_options', 1);
add_action( 'edit_user_profile', 'pushit_on_edit_user_profile', 1);

function pushit_login_head(){
	if (isset($_GET['pushit_message'])) {
		global $error;
		$GLOBAL['error'] = $_GET['pushit_message'];
	}
	
	pushit_head();
}

function generate_and_save_phone_activation_code($user_id){
    $phone_number_activation_code = wp_generate_password();
    update_usermeta($user_id, 'phone_number_activated', $phone_number_activation_code);
	return $phone_number_activation_code;
}

function pushit_user_profile_update($user){
  global $pushit_errors;
  check_mobile_phone_number('', '', $pushit_errors, false);
  if (count($pushit_errors->get_error_codes()) == 0){
	$old_mobile = get_usermeta($_POST['user_id'], 'phone_number_all');
	$new_mobile = $_POST['user_phone_cc']."<-->".$_POST['user_phone'];
//echo '$user = `'.$user.'`<br>$old_mobile = `'.$old_mobile.'`<br>$new_mobile = `'.$new_mobile.'`';
//exit(0);
	if ($old_mobile != $new_mobile){
		delete_usermeta( $_POST['user_id'], 'phone_number_activated');
	}
    save_phone($user);
  }
}

/*
occurs when user regitration completes
*/
function pushit_on_user_register($user){
  save_phone($user, false);
}

function pushit_on_edit_user_profile(){
	if (isset($_REQUEST['user_id']) && ($_REQUEST['user_id'] > 0)){
		$user_id = $_REQUEST['user_id'];
	} else {
		return;
	}
    $phone_number_activated = get_usermeta($user_id, 'phone_number_activated');
    if (!isset($phone_number_activated) || ($phone_number_activated != 1)){ // probably user have no previously mobile number entered. He must to activate it.
      echo '
  <script language="javascript">
      document.getElementById("pushit_phone_activation").style.display="";
  </script>
      ';
    } else {
      echo '
  <script language="javascript">
      document.getElementById("pushit_phone_activation").style.display="none";
  </script>
      ';
    }
}
/*
this adds filed to enter mobile phone activation code if user just entered it
if mobile phone number already activated nothing is added
*/
function pushit_on_profile_personal_options ($data){
	if (isset($_REQUEST['user_id']) && ($_REQUEST['user_id'] > 0)){
		$user_id = $_REQUEST['user_id'];
	} else {
		$user = wp_get_current_user();
		$user_id = $user->ID;
	}
    $phone_number_activated = get_usermeta($user_id, 'phone_number_activated');
    if (!isset($phone_number_activated) || ($phone_number_activated != 1)){ // probably user have no previously mobile number entered. He must to activate it.
      echo '
  <script language="javascript">
    function pushit_phone_activation(){
      document.getElementById("pushit_phone_activation").style.display="";
    }
  </script>
      ';
    } else {
      echo '
  <script language="javascript">
    function pushit_phone_activation(){
      document.getElementById("pushit_phone_activation").style.display="none";
    }
  </script>
      ';
    }
}

/*
adding additional fields to user login form
*/
function pushit_on_login_form() {
	echo '
	<p class="pushit_login_label">
		<label for="user_phone_cc">Mobile phone number</label><br />	
		+<input type="text" name="user_phone_cc" id="user_phone_cc" class="input" value="" size="4" tabindex="21" onfocus="pushit_input_hint_focus(this)" onblur="pushit_input_hint_blur(this)"  title="46"/>&nbsp;<input type="text" name="user_phone" id="user_phone" class="input" value="" size="20" tabindex="22" onfocus="pushit_input_hint_focus(this)" onblur="pushit_input_hint_blur(this)" title="123456789" /><br />
	</p>
<script type="text/javascript">
	pushit_input_hint_blur($("user_phone_cc"));
	pushit_input_hint_blur($("user_phone"));
</script>
';

}

/*
runs when user requests lost password using mobile phone number
*/
function pushit_on_lostpassword_post(&$errors, $user_data = null){
  $email_or_user_name_entered = true;
  foreach ($errors->get_error_codes() as $code) {
	  if (in_array($code, array('empty_username'))) {
	    $email_or_user_name_entered = false;
	    break;
	  }
  }
  if ($email_or_user_name_entered){
    return;
  }
  if (!validate_mobile_number(build_mobile($_POST['user_phone_cc'], $_POST['user_phone']), $errors)){
    return;
  }
  $id = get_user_id_by_phone($_POST['user_phone_cc'], $_POST['user_phone']);
  if (empty($id)){
    $errors->add('invalid_phone', __('<strong>ERROR</strong>: The phone is not found.'));
    return;
  }
    $user_data = get_userdata($id);
  $new = new WP_Error();
  foreach ($errors->get_error_codes() as $code) {
	  if (in_array($code, array('empty_username', 'invalid_email', 'invalidcombo'))) continue;

	  $message = $errors->get_error_message($code);
	  $data = $errors->get_error_data($code);
	  $new->add($code, $message, $data);
  }

  $GLOBALS['pushit']['err'] = &$new;
//  wp_redirect('wp-login.php?pushit_message=newpass');
      $new_pass = wp_generate_password( 5, false ); // Generate something random for a key...
      SMS::setCredentials( get_option( 'pushit_sms_service_login' ), get_option( 'pushit_sms_service_password' ));
	  $mobile = get_user_mobile($id);
	  $mobilForwardOK = get_option('pushit_mobilforward_order_key');
	  if (isset($mobilForwardOK) && ($mobilForwardOK != '')) { // we should send and sms with activation code to charge him for restoring pass
		update_usermeta($user_data->ID, 'pushit_new_pass_code', $new_pass);
		$msg = $mobilForwardOK.$new_pass;
		SMS::send( $mobile,  $msg , get_option('pushit_sender_name'));
		wp_redirect('wp-login.php?pushit_message=restorepass&code='.$new_pass);
	  } else { // simply send new pass to user
	
		  wp_set_password( $new_pass, $user_data->ID );
		  do_action( 'password_reset', $user_data );
		  
		  $msg = build_template( get_option( 'pushit_reminder_format' ), array(
			'[BLOG_NAME]' => get_bloginfo('name'),
			'[BLOG_URL]' => get_option('siteurl') . ' ',
			'[NAME]' => coalesce( $user_data->display_name, $user_data->login ),
			'[PASS]' => $new_pass
			)
		  );
		  SMS::send( $mobile,  $msg , get_option('pushit_sender_name'));
		  wp_redirect('wp-login.php?pushit_message=newpass');
		  exit(0);
	  }
}

/*
adding fields to user register form
*/
function pushit_block_register_form (){
echo '
	<p class="pushit_login_label">
		<label for="user_phone_cc">or Mobile phone number</label><br />	
		+<input type="text" name="user_phone_cc" id="user_phone_cc" class="input" value="" size="4" tabindex="21" onfocus="pushit_input_hint_focus(this)" onblur="pushit_input_hint_blur(this)"  title="46"/>&nbsp;<input type="text" name="user_phone" id="user_phone" class="input" value="" size="20" tabindex="22" onfocus="pushit_input_hint_focus(this)" onblur="pushit_input_hint_blur(this)" title="123456789" /><br />
	</p>
<script type="text/javascript">
	pushit_input_hint_blur($("user_phone_cc"));
	pushit_input_hint_blur($("user_phone"));
</script>
	';
}

/*
overriding default function to send new user password by sms
*/
if ( !function_exists('wp_new_user_notification') ) {
function wp_new_user_notification($user_id, $user_pass = ""){
      if (empty($user_pass)) {
		return;
      }
      $u = get_userdata($user_id);
      $msg = build_template( get_option( 'pushit_registration_format' ), array(
			       '[BLOG_NAME]' => get_bloginfo('name'),
						 '[BLOG_URL]' => get_option('siteurl') . ' ',
        '[NAME]' => coalesce( $u->display_name, $u->login ),
        '[PASS]' => $user_pass
        )
	  );
//      $msg = sprintf( "Hi %s! Your new password is %s", $u->user_login, $user_pass );
      $m = get_user_mobile($user_id);
      $l = get_option( 'pushit_sms_service_login' );
      $p = get_option( 'pushit_sms_service_password' ) ;
      SMS::setCredentials( $l, $p);
      SMS::send( $m,  $msg, get_option('pushit_sender_name') );
	  	update_usermeta($user_id, 'phone_number_activated', '1');
			wp_redirect('/wp-login.php?pushit_message=registered');
			exit(0);
}
}

/*
get two separate parts of mobile number -country code and number- as arguments
and returns as string +123456789012
*/
function build_mobile($c_code, $phone_number) {
	if (($c_code == '') && ($phone_number == '')){
	  return '';
	}
	$mobile = $c_code.$phone_number;
	if (strpos($mobile,'+')!==0) $mobile='+'.$mobile;
	return $mobile; 	
}

function pushit_divide_mobile_on_two_parts($mobile){
  if (preg_match('~(.*?)\<--\>(.*)~', $mobile, $matches)){
    if ((isset($matches[1])) && (isset($matches[2]))){
      return array($matches[1], $matches[2]);
    }
  }
  return array("",$mobile);
}
/*
get full mobile number from given user object or from user's data from database
*/
function get_user_mobile($user){
  if(is_object($user)) {
  	$mobile = build_mobile($user->phone_number_cc,$user->phone_number);
  } else {
  	$mobile = build_mobile(get_usermeta($user, 'phone_number_cc'), get_usermeta($user, 'phone_number'));
  }
  return $mobile;
}
/*
authenticate user by his mobile phone number
*/
function pushit_on_wp_authenticate(&$credentials){
if (empty($_POST)){
return;
  }
$num = func_num_args();
global $errors;

  $mobile = build_mobile(@$_POST['user_phone_cc'], @$_POST['user_phone']);
  if ($mobile == ''){
    return;
  }
  $user = wp_authenticate_pushit($mobile, $_POST['pwd']);
  if (is_wp_error($user)){
    $errors = $user;
    return;
  }
  wp_set_auth_cookie($user->ID, $_POST['remember'], false);
  wp_redirect(get_option('pushit_redirectURL'));
  exit(0);
}
/*
validating user data to login by mobile phone number
*/
function wp_authenticate_pushit($username, $password) {
//	$username = sanitize_user($username);

	if ( '' == $username ) {
	  return new WP_Error('empty_username', __('<strong>ERROR</strong>: The username field is empty.'));
	}

	if ( '' == $password ) {
		return new WP_Error('empty_password', __('<strong>ERROR</strong>: The password field is empty.'));
	}
	$user_id = get_user_id_by_phone($_POST['user_phone_cc'], $_POST['user_phone']);
	if(!empty($user_id)) {
	  $user = get_userdata( $user_id );
	  $username = $user->user_login;
	} else {
	  $user = get_userdatabylogin($username);
	}

	if ( !$user || ($user->user_login != $username) ) {
		do_action( 'wp_login_failed', $username );
		return new WP_Error('invalid_username', __('<strong>ERROR</strong>: Invalid username or mobile phone.'));
	}

	$user = apply_filters('wp_authenticate_user', $user, $password);
	if ( is_wp_error($user) ) {
		do_action( 'wp_login_failed', $username );
		return $user;
	}

	if ( !wp_check_password($password, $user->user_pass, $user->ID) ) {
		do_action( 'wp_login_failed', $username );
		return new WP_Error('incorrect_password', __('<strong>ERROR</strong>: Incorrect password.'));
	}

	return new WP_User($user->ID);
}

/*
adding fields to registeration form
*/
function pushit_add_fields_to_register_form(){
$wp_url = get_bloginfo( 'wpurl', 'display' );
$plugin_url = $wp_url . '/wp-content/plugins/pushit/';
echo '	<p>&nbsp;</p><p class="pushit_login_label">
		<label for="user_phone_cc">Mobile phone number</label><br />	
		+<input type="text" name="user_phone_cc" id="user_phone_cc" class="input" value="" size="4" tabindex="21" onfocus="pushit_input_hint_focus(this)" onblur="pushit_input_hint_blur(this)"  title="46"/>&nbsp;<input type="text" name="user_phone" id="user_phone" class="input" value="" size="20" tabindex="22" onfocus="pushit_input_hint_focus(this)" onblur="pushit_input_hint_blur(this)" title="123456789" /><br />
	</p>
<script type="text/javascript">
	pushit_input_hint_blur($("user_phone_cc"));
	pushit_input_hint_blur($("user_phone"));
</script>
	<p>
      <input type="checkbox" id="user_license" name="user_license" value="1" /><a href="'.$plugin_url.'pushit_pages.php?action=pushit_license_text" target="_blank">User agreement confirmed</a></p>
      <p><input type="checkbox" id="mobilstart_user_license" name="mobilstart_user_license" value="1" /><a href="https://mobilstart.telenor.se/web/guest/villkor" target="_blank" id="mobilstart_user_agr_link">Mobilstart User agreement</a>
	</p>
	<p>&nbsp;</p>
';
}

// action register_post is for validate user's mobile
function check_mobile_phone_number($user_login_name, $user_email, &$errors, $do_check_licenses = true){

  if($do_check_licenses){
    if(!isset($_POST['user_license']) || !isset($_POST['mobilstart_user_license'])){
      $errors->add('empty_user_license', __('<strong>ERROR</strong>: You have to agree with both agreements.'));
    }
  }

  $mobile = build_mobile($_POST['user_phone_cc'], $_POST['user_phone']);
  
  if ($mobile == '+46123456789') {
    $errors->add('empty_phone', __('<strong>ERROR</strong>: The phone field is empty.'));
    return;
  }
  
  if (!validate_mobile_number($mobile, &$errors)){
    return;
  }
  
  $id = get_user_id_by_phone($_POST['user_phone_cc'], $_POST['user_phone']);
  $user = wp_get_current_user();
  if ($do_check_licenses) { // this means that it is a registration process and we do not have user_id yet
	$it_is_mine_phone = true;
  } else{
	$it_is_mine_phone = $_POST['user_id'] == $id;
  }
  
  if (!empty($id) && (!$it_is_mine_phone)){
    $errors->add('invalid_phone', __('<strong>ERROR</strong>: The phone is already taken.'));
    return;
  }
}

// this function accepts two parameters:
// $mobile - mobile number to validate
// $errors - WP_Error object by reference
function validate_mobile_number($mobile, &$errors){
  if(empty($mobile)) {
    $errors->add('empty_phone', __('<strong>ERROR</strong>: The phone field is empty.'));
    return false;
  }
  if (!validate_mobile($mobile)) {
    $errors->add('invalid_phone', __('<strong>ERROR</strong>: The phone is invalid.'));
    return false;
  }
  return true;
}


/*
retrieval user_id from his mobile stored in the database
*/
function get_user_id_by_phone($cc, $phone){
  global $wpdb;
  $id = $wpdb->get_var( $wpdb->prepare( "SELECT `user_id` FROM $wpdb->usermeta WHERE `meta_key` = 'phone_number_all' AND  `meta_value` = %s limit 1",$cc."<-->".$phone ) );

  if (!empty($id)){
    return $id;
  } else {
    return null;
  }
}
if ( !function_exists('wp_generate_password') ) :
/**
 * Generates a random password drawn from the defined set of characters.
 *
 * @since 2.5
 *
 * @return string The random password
 **/
function wp_generate_password($length = 5, $special_chars = false) {
	$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
	if ( $special_chars )
		$chars .= '!@#$%^&*()';

	$password = '';
	for ( $i = 0; $i < $length; $i++ )
		$password .= substr($chars, wp_rand(0, strlen($chars) - 1), 1);
	return $password;
}
endif;

/*
saving phone number to database
*/
function save_phone($user_id, $check_activation = true){ // added by Konstantin Maximchik
  if ((!isset($user_id) || ($user_id == '')) && !empty($_POST['user_id'])) {
    $u = $_POST['user_id'];
  } else {
    $u = $user_id;
  }
  if ($check_activation){
    $phone_number_activated = get_usermeta($u, 'phone_number_activated');
    if (!isset($phone_number_activated) || ($phone_number_activated != '1')){ // probably user have no previously mobile number entered. He must to activate it.
      if (isset($_POST['phone_number_activation_code']) && ($_POST['phone_number_activation_code'] != '') && ($phone_number_activated == $_POST['phone_number_activation_code'])){
	update_usermeta($u, 'phone_number_activated', '1');
      }
    }
  }
  update_usermeta($u, 'phone_number_cc', $_POST['user_phone_cc']);
// for some strange reason WP does not allow to save value with leading zero using update_usermeta function.
  update_usermeta($u, 'phone_number', $_POST['user_phone']);
  $saved_number = get_usermeta($u, 'phone_number');
  global $wpdb;
  if (isset($saved_number)){
    $wpdb->query( $wpdb->prepare("UPDATE $wpdb->usermeta SET meta_value = %s WHERE user_id = %d AND meta_key = %s", $_POST['user_phone'], $u, 'phone_number') );
  } else {
    $wpdb->query( $wpdb->prepare("INSERT into $wpdb->usermeta SET meta_value = %s, user_id = %d, meta_key = %s", $_POST['user_phone'], $u, 'phone_number') );
  }
  update_usermeta($u, 'phone_number_all', $_POST['user_phone_cc']."<-->".$_POST['user_phone']);
  return;
}

/*
main plugin function
*/
function pushit()
{
  if( defined( 'Pushit CALLED' ) )
  {
    return; //single call on a page please
  }
  
  output_js_bootstrap();
  
  global $user_identity, $current_user;
  
  $filename = TEMPLATEPATH . '/pushit_template.php';
  if ( !file_exists( $filename ) )
  {
		$filename = 'pushit_template.php';
	}
  include( $filename );

  
  define( 'Pushit CALLED', true );
}

/*
probably not needed function
*/
function output_js_bootstrap()
{
  if( defined( 'Pushit bootstrap CALLED' ) ) {
    return; //single call on a page please
  }
  global $user_identity, $current_user;
  get_currentuserinfo();
  $user_identity = empty( $current_user->display_name ) ? $current_user->user_login : $current_user->display_name;
  
  $wp_url = get_bloginfo( 'wpurl', 'display' );
  $plugin_url = $wp_url . '/wp-content/plugins/pushit/';
  
/*  echo '<script type="text/javascript">
	var user_identity = "' . htmlspecialchars( $user_identity ) . '";
	var pushit_redirectURL = "' . get_option( 'pushit_redirectURL' ) . '";
	var post_id = 0;
	var GB_ROOT_DIR = "' . $plugin_url . 'greybox/";
</script>
  <script type="text/javascript" src="' . $plugin_url . 'greybox/AJS.js"></script>
  <script type="text/javascript" src="' . $plugin_url . 'greybox/AJS_fx.js"></script>
  <script type="text/javascript" src="' . $plugin_url . 'greybox/gb_scripts.js"></script>';

	$referer = parse_url($_SERVER['HTTP_REFERER']);
	if (basename($referer['path'])=='wp-login.php' && (strpos($referer['query'], 'action=register')!==false)) {
		echo '<script>GB_showCenter("REGISTER", "'.$plugin_url. 'pushit_pages.php?action=register'.'", 270, 200 )</script>';
	} else if (basename($referer['path'])=='wp-login.php' && (strpos($referer['query'], 'action=lospassword')!==false)) {
		echo '<script>GB_showCenter("FORGOT PASS?", "'.$plugin_url . 'pushit_pages.php?action=forgotpass'.'", 200, 200 )</script>';
	} else if (basename($referer['path'])=='wp-login.php' ) {
		echo '<script>GB_showCenter("LOGIN", "'.$plugin_url . 'pushit_pages.php?action=login'.'", 200, 200 )</script>';
	} */
  define( 'Pushit bootstrap CALLED', true );
}

/*
private function to check permissions on files to be updated by plugin installer
*/
function get_file_paths() {
  $dir = getcwd();
  if (strpos($dir, '\\')!=FALSE) {
    $pd = '\\';
  } else {
	$pd = '/';
  }
  $dir = preg_replace('/\\'.$pd.'wp-admin$/',$pd,$dir);
//return;
  $wp_login_fname = $dir.'wp-login.php';
  if (!is_writable($wp_login_fname)){
    wp_die("File `".$wp_login_fname."` must be writable");
  }
  $dir .= 'wp-admin'.$pd;
  $wp_admin_tpl_fname = $dir."includes/template.php";
  if (!is_writable($wp_admin_tpl_fname)){
    wp_die("File `".$wp_admin_tpl_fname."` must be writable");
  }
  $wp_user_edit_fname = $dir."user-edit.php";
  if (!is_writable($wp_user_edit_fname)){
    wp_die("File `".$wp_user_edit_fname."` must be writable");
  }
  $wp_user_new_fname = $dir."user-new.php";
  if (!is_writable($wp_user_new_fname)){
    wp_die("File `".$wp_user_new_fname."` must be writable");
  }
  return array($wp_login_fname,$wp_admin_tpl_fname,$wp_user_edit_fname,$wp_user_new_fname);
}

/*
removing changes made while activatin plugin
*/
function pushit_deactivate(){
  list($wp_login_fname,$wp_admin_tpl_fname,$wp_user_edit_fname,$wp_user_new_fname) = get_file_paths();


  $wp_login_file = file_get_contents($wp_login_fname);
  $reg = '/(.*?function retrieve_password.*?do_action\(\'lostpassword_post\').*?\/\*pushit\*\/(.*?)\$do_send_email = true; \/\*pushit\*\/.*?\/\*pushit\*\/(.*?)if \(isset\(\$do_send_email\)&&!\$do_send_email\) return true;(.*)/s';
  preg_match($reg, $wp_login_file, $matches);

  $wp_login_file = $matches[1].");\n".$matches[2].'do_action(\'retrieve_password_key\', $user_login, $key);'.$matches[3].$matches[4];
  
  $reg = '~(.*?)\/\*pushit_messages_start\*\/.*?\/\*pushit_messages_stop\*\/(.*)~s';
  preg_match($reg, $wp_login_file, $matches);
  $wp_login_file = $matches[1].$matches[2];
  
  $wp_login = fopen($wp_login_fname, 'w');
  fwrite($wp_login, $wp_login_file);
  fclose($wp_login);
  $wp_admin_tpl_file = file_get_contents($wp_admin_tpl_fname);
  $reg = '/(.*?)\/\*pushit_users_table_header_start\*\/.*?\/\*pushit_users_table_header_stop\*\/(.*?)\/\*pushit_user_row_phone_variable_define_start\*\/.*?\/\*pushit_user_row_phone_variable_define_stop\*\/(.*?)\/\*pushit_user_row_phone_variable_insert_start\*\/.*?\/\*pushit_user_row_phone_variable_insert_stop\*\/(.*)/s';

  preg_match($reg, $wp_admin_tpl_file, $matches);
  
  $wp_admin_tpl_file = $matches[1].$matches[2].$matches[3].$matches[4];
  
  $wp_admin_tpl = fopen($wp_admin_tpl_fname, 'w');
  fwrite($wp_admin_tpl, $wp_admin_tpl_file);
  fclose($wp_admin_tpl);
  
  $wp_user_edit_file = file_get_contents($wp_user_edit_fname);

  $reg = '/(.*?)<!--pushit_user_edit_phone_add_start-->.*?<!--pushit_user_edit_phone_add_stop-->(.*)/s';
  preg_match($reg, $wp_user_edit_file, $matches);

  $wp_user_edit_file = $matches[1].$matches[2];

  $reg = '/(.*?)\/\*pushit_user_edit_actions_start\*\/.*?\/\*pushit_user_edit_actions_stop\*\/(.*?)\/\*pushit_user_edit_actions_start1\*\/.*?\/\*pushit_user_edit_actions_stop1\*\/(.*)/s';
  preg_match($reg, $wp_user_edit_file, $matches);

  $wp_user_edit_file = $matches[1].$matches[2].$matches[3];

  $wp_user_edit = fopen($wp_user_edit_fname, 'w');
  fwrite($wp_user_edit, $wp_user_edit_file);
  fclose($wp_user_edit);
//========================  

  $wp_user_new_file = file_get_contents($wp_user_new_fname);

  $reg = '/(.*?)\/\*pushit_start1\*\/.*?\/\*pushit_stop1\*\/(.*?)\/\*pushit_start2\*\/.*?\/\*pushit_stop2\*\/(.*)/s';
  preg_match($reg, $wp_user_new_file, $matches);
  $wp_user_new_file = $matches[1].$matches[2].$matches[3];

  $reg = '/(.*?)<!--pushit_user_new_phone_add_start-->.*?<!--pushit_user_new_phone_add_stop-->(.*)/s';
  preg_match($reg, $wp_user_new_file, $matches);

  $wp_user_new_file = $matches[1].$matches[2];

  $wp_user_new = fopen($wp_user_new_fname, 'w');
  fwrite($wp_user_new, $wp_user_new_file);
  fclose($wp_user_new);
    global $wpdb;
    $wpdb->query($wpdb->prepare('DROP TABLE IF EXISTS `'.$wpdb->prefix.'pushit_bitly_cache`'));

}

/*
make needed changes to WP files
*/
function pushit_init() {
  list($wp_login_fname,$wp_admin_tpl_fname,$wp_user_edit_fname,$wp_user_new_fname) = get_file_paths();

  $wp_login_file = file_get_contents($wp_login_fname);
  $reg = '/(.*id="loginform".*?\<label\>)(\<\?php _e\(\')(Username)(\'\) \?>)(<br \/\>.*)/s';
  $reg = '/(.*?function retrieve_password.*?do_action\(\'lostpassword_post\')\);(.*?)do_action\(\'retrieve_password_key\', \$user_login, \$key\);(.*?)(\$message = __\(\'Someone has asked.*)/s';
  preg_match($reg, $wp_login_file, $matches);

    $wp_login_file = $matches[1].', $errors, $user_data); /*pushit*/'.$matches[2]."\n\$do_send_email = true; /*pushit*/\ndo_action('retrieve_password_key', \$user_login, \$key, &\$do_send_email);/*pushit*/\n".$matches[3]."\n".'if (isset($do_send_email)&&!$do_send_email) return true;'."\n".$matches[4];


  $reg = '~(.*?)(login_header\(__\(\'Log In\'\), \'\', \$errors\);.*)~s';
  preg_match($reg, $wp_login_file, $matches);
  $data = '
	/*pushit_messages_start*/elseif	( isset($_GET[\'pushit_message\']) && \'registered\' == $_GET[\'pushit_message\'] )	$errors->add(\'registered\', __(\'Registration complete. Please check your incoming SMS for the password to \'.get_option(\'blogname\').\'.\'), \'message\');
	elseif	( isset($_GET[\'pushit_message\']) && \'newpass\' == $_GET[\'pushit_message\'] )	$errors->add(\'registered\', __(\'Please check your incoming SMS for the new password to \'.get_option(\'blogname\').\'.\'), \'message\');
	elseif	( isset($_GET[\'pushit_message\']) && \'restorepass\' == $_GET[\'pushit_message\'] )	{
		$pushit_restorepass_code = get_option(\'pushit_mobilforward_order_key\').$_GET[\'code\'];
		$errors->add(\'registered\', __(\'An SMS with text: <strong>\'.$pushit_restorepass_code.\'</strong> was sent to you mobile. Please forward it to number 72500 without any changes. Or just send this text to 72500.\', \'message\'));
	}
	/*pushit_messages_stop*/
  
';
  $wp_login_file = $matches[1].$data.$matches[2];

  $wp_login = fopen($wp_login_fname, 'w');
  fwrite($wp_login, $wp_login_file);
  fclose($wp_login);
  
  $wp_admin_tpl_file = file_get_contents($wp_admin_tpl_fname);
  $reg = '/(.*function get_column_headers\(.*?case \'users\':.*?__\(\'Posts\'\))(.*)/s';
  preg_match($reg, $wp_admin_tpl_file, $matches);

  $wp_admin_tpl_file = $matches[1]."/*pushit_users_table_header_start*/, 'phone' => __('Phone') /*pushit_users_table_header_stop*/".$matches[2];

  $reg = '/(.*function user_row\(.*?)(foreach \( \$columns as \$column_name =\> \$column_display_name \) \{.*?case \'posts\':.*?)(\$r \.= "\<\/td\>";.*)/s';
  preg_match($reg, $wp_admin_tpl_file, $matches);

  $wp_admin_tpl_file = $matches[1]."/*pushit_user_row_phone_variable_define_start*/\n".'$phone = isset($user_object->phone_number) ? get_user_mobile($user_object) : "not set";'."\n/*pushit_user_row_phone_variable_define_stop*/".$matches[2].'/*pushit_user_row_phone_variable_insert_start*/break; case \'phone\': $r .= "<td $attributes>$phone</td>";/*pushit_user_row_phone_variable_insert_stop*/'.$matches[3];
  
  $wp_admin_tpl = fopen($wp_admin_tpl_fname, 'w');
  fwrite($wp_admin_tpl, $wp_admin_tpl_file);
  fclose($wp_admin_tpl);

  $wp_user_edit_file = file_get_contents($wp_user_edit_fname);
  
  $reg = '~(.*?)(if \(\$is_profile_page\).*?do_action\(\'edit_user_profile_update\'\);)(.*)~s';
  preg_match($reg, $wp_user_edit_file, $matches);
  $part1 = '
/*pushit_user_edit_actions_start*/global $pushit_errors;
$pushit_errors = new WP_Error();/*pushit_user_edit_actions_stop*/
';
  $part2 = '
/*pushit_user_edit_actions_start1*/if (count($pushit_errors->get_error_codes()) > 0)
$errors = $pushit_errors;
else/*pushit_user_edit_actions_stop1*/
';
  $wp_user_edit_file = $matches[1].$part1.$matches[2].$part2.$matches[3];

  $reg = '/(.*?\<h3\>\<\?php _e\(\'Contact Info\'\) \?\>\<\/h3\>.*?\<table class="form-table"\>)(.*)/s';
  preg_match($reg, $wp_user_edit_file, $matches);

  $wp_url = get_bloginfo( 'wpurl', 'display' );
  $plugin_url = $wp_url . '/wp-content/plugins/pushit/';
  $data = '<!--pushit_user_edit_phone_add_start-->
  <tr>
	  <th><label for="phone"><a name="mobile_phone"><?php _e(\'Mobile phone\') ?></a></label></th>
	  <td>+<input type="text" name="user_phone_cc" id="user_phone_cc" value="<?php echo $profileuser->phone_number_cc ?>" style="width:50px" />
		<input type="text" name="user_phone" id="user_phone" value="<?php echo $profileuser->phone_number ?>" class="regular-text"  style="width:235px"/></td>
  </tr>
  <tr id="pushit_phone_activation">
	  <th><label for="phone_number_activation_code">Phone number activation code</label></th>
	  <td><input type="text" name="phone_number_activation_code" id="phone_number_activation_code" value="" class="regular-text" />
	  <a id="phone_number_get_ac_link" href="'.$plugin_url.'pushit_pages.php?action=pushit_send_phone_number_activation_code" target="_blank">Click here to get it to your phone</a>
	  </td>
  </tr>
  <script language="javascript">
    pushit_phone_activation();
  </script>
  <!--pushit_user_edit_phone_add_stop-->';
  $wp_user_edit_file = $matches[1].$data.$matches[2];

  $wp_user_edit = fopen($wp_user_edit_fname, 'w');
  fwrite($wp_user_edit, $wp_user_edit_file);
  fclose($wp_user_edit);
//======
  $wp_user_new_file = file_get_contents($wp_user_new_fname);
  
  $reg = '/(.*?require_once\( ABSPATH \. WPINC \. \'\/registration\.php\'\).*?)(\$user_id = add_user\(\);)(.*)/s';
  preg_match($reg, $wp_user_new_file, $matches);

  $wp_user_new_file = $matches[1].'
	/*pushit_start1*/
	$pushit_errors = new WP_Error();
	check_mobile_phone_number(\'\', \'\', $pushit_errors);
	if ( count($pushit_errors->get_error_codes()) == 0 ) {
	/*pushit_stop1*/
	'.$matches[2].'
	/*pushit_start2*/
	} else {
	  $user_id = $pushit_errors;
	}
	/*pushit_stop2*/
  '.$matches[3];

  $reg = '/(.*\<\?php _e\(\'E-mail \(required\)\'\) \?\>.*?\<\/tr\>)(.*)/s';
  preg_match($reg, $wp_user_new_file, $matches);

  $data = '<!--pushit_user_new_phone_add_start-->
	<tr class="form-field form-required">
		<th scope="row"><label for="phone"><?php _e(\'Phone (required)\') ?></label></th>
		<td>+<input type="text" name="user_phone_cc" id="user_phone_cc" value="<?php echo $new_user_phone_cc; ?>" style="width:10%" />
<input name="user_phone" type="text" id="user_phone" value="<?php echo $new_user_phone; ?>" style="width:80%" /><input type="hidden" name="user_license" value="1"/><input type="hidden" name="mobilstart_user_license" value="1"/></td>
	</tr>
  <!--pushit_user_new_phone_add_stop-->';
  $wp_user_new_file = $matches[1].$data.$matches[2];

  $wp_user_new = fopen($wp_user_new_fname, 'w');
  fwrite($wp_user_new, $wp_user_new_file);
  fclose($wp_user_new);

  if (!get_option('pushit_license_text') && is_readable( dirname(__FILE__) . '/user_agreement.default.html' ) ) {
	    update_option( 'pushit_license_text', file_get_contents(dirname(__FILE__).'/user_agreement.default.html') );
    }
    if (!get_option('mobilstart_license_text') && is_readable( dirname(__FILE__) . '/user_agreement.mobilstart.html' ) ) {
	    update_option( 'mobilstart_license_text', file_get_contents(dirname(__FILE__).'/user_agreement.mobilstart.html') );
    }
    if (!get_option('about_pushit_plugin') && is_readable( dirname(__FILE__) . '/about.html' ) ) {
	    update_option( 'about_pushit_plugin', file_get_contents(dirname(__FILE__).'/about.html') );
    }
    update_option( 'pushit_reminder_format', "Hi [NAME], your new password is [PASS].\n\nPlease visit [BLOG_NAME] at [BLOG_URL] with your mobile device - happy surfing!" );
    update_option( 'pushit_registration_format', "Hi [NAME], your password is [PASS].\n\nPlease visit [BLOG_NAME] at [BLOG_URL] with your mobile device - happy surfing!" );
    update_option( 'pushit_msg_format', "Hi again, it's [NAME]!\nI'd really like to recommend this article: [URL]\n\n[NAME], [PHONE]" );
    update_option('pushit_bitly_login', '');
    update_option('pushit_bitly_apikey', ''); 
    update_option('pushit_mobilforward_order_key', ''); 
    update_option('pushit_sender_name', get_option('blogname')); 
    global $wpdb;
    $wpdb->query($wpdb->prepare('DROP TABLE IF EXISTS `'.$wpdb->prefix.'pushit_bitly_cache`'));
//$wpdb->print_error(); exit(0);
$sql = 'CREATE TABLE `'.$wpdb->prefix.'pushit_bitly_cache` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`url` VARCHAR( 255 ) NOT NULL,
`shorturl` VARCHAR( 255 ) NOT NULL
) ENGINE = MYISAM';
$wpdb->query($wpdb->prepare($sql));

}

/*
adding plugin's css and js
*/
function pushit_head() {
	wp_print_scripts( array( 'sack' ));
  
  $wp_url = get_bloginfo( 'wpurl', 'display' );
  $plugin_url = $wp_url . '/wp-content/plugins/pushit/';
  
  echo '<link rel="stylesheet" href="' . $plugin_url . 'pushit.css" type="text/css" media="screen" />
<link rel="stylesheet" href="' . $plugin_url . 'pushit_print.css" type="text/css" media="print" />
		<link rel="stylesheet" href="' . $plugin_url . 'greybox/gb_styles.css" type="text/css" />
		<script type="text/javascript" src="' . $plugin_url . 'pushit.js"></script>
		<script type="text/javascript">var pushit_base_uri = "' . addslashes($wp_url) . '"</script>';
// echo "<script type='text/javascript' src='http://getfirebug.com/releases/lite/1.2/firebug-lite-compressed.js'></script>";
}

/*
adding pushit menu item to admin area
*/
function pushit_add_optionsmenu()
{
  add_options_page( 'Pushit Settings', 'Pushit', 10, 'pushit', 'pushit_optionspage' );
}

/*
plugin's options page in admin area
*/
function pushit_optionspage() {
	if ( !current_user_can( 'manage_options' ) )
		wp_die( __('Cheatin&#8217; uh?') );
	$pushit_message = false;
	$pushit_message_css = 'updated fade';
  if ( !empty( $_POST['update'] ) ) {
    // validation
	// checking sender name
	if (strlen($_POST['pushit_sender_name']) > 11){
		$pushit_message = 'Sender name should have maximum 11 chars';
	}
	
	if($pushit_message === false){
		if(preg_match('~[^a-zA-Z\d]~', $_POST['pushit_sender_name'])){
			$pushit_message = 'For sender name only letters and digits are allowed.';
		}
	}
	
	if ($pushit_message === false) {
		// updating parameters
		$value = clean_url( stripslashes( trim( $_POST[ 'pushit_redirectURL' ] ) ) );
		update_option( 'pushit_redirectURL', $value );

		update_option( 'pushit_sms_service_login', trim( $_POST[ 'pushit_sms_service_login' ] ) );
		update_option( 'pushit_sms_service_password', trim( $_POST[ 'pushit_sms_service_password' ] ) );
		update_option( 'pushit_disablerecommend', isset($_POST['pushit_disablerecommend']) ? '1' : '0' );
		
		$license = trim( stripcslashes( $_POST[ 'pushit_license_text' ] )) ;
		update_option( 'pushit_license_text', $license );
		
		$license_mobilstart = trim( stripcslashes( $_POST[ 'mobilstart_license_text'] )) ;

		$format = trim( stripcslashes( $_POST[ 'pushit_msg_format' ] )) ;
		update_option( 'pushit_msg_format', $format );
		
		$format = trim( stripcslashes( $_POST[ 'pushit_reminder_format' ] )) ;
		update_option( 'pushit_reminder_format', $format );
		
		$format = trim( stripcslashes( $_POST[ 'pushit_registration_format' ] )) ;
		update_option( 'pushit_registration_format', $format );
		
		update_option('pushit_bitly_apikey', addslashes($_POST['pushit_bitly_apikey']));
		update_option('pushit_bitly_login', addslashes($_POST['pushit_bitly_login']));
		update_option('pushit_sender_name', addslashes($_POST['pushit_sender_name']));
		update_option('pushit_mobilforward_order_key', addslashes($_POST['pushit_mobilforward_order_key']));
		$pushit_message = "Options has been saved.";
	} else {
		$pushit_message_css = 'error login';
	}
}

?>

<?php if ($pushit_message !== false): ?><div id="message" class="<?=$pushit_message_css;?>"><p><strong><?php _e($pushit_message) ?></strong></p></div><?php endif; ?>

<div class="wrap">
  <h2><?php _e('Pushit Settings') ?></h2>
  <form method="post" action="options-general.php?page=pushit">
    <?php wp_nonce_field('update-pushitoptions') ?>

    <table class="optiontable">
    
      <tr>
        <th scope="row" align="right"><?php _e('Redirect URL:') ?></th>
        <td>
          <input name="pushit_redirectURL" type="text" id="pushit_redirectURL" style="width: 460px" value="<?php form_option('pushit_redirectURL'); ?>" size="45" />
          <br />
          <?php _e('Optional redirect page after a successful login. The user will stay on the same page if this field is left blank.') ?>
        </td>
      </tr>
      
      <tr>
        <th scope="row" align="right"><?php _e('mobilstart.se Login:') ?></th>
        <td>
          <input name="pushit_sms_service_login" type="text" id="pushit_sms_service_login" style="width: 460px" value="<?php form_option('pushit_sms_service_login'); ?>" /> <a target="_blank" href="http://mobilstart.se/">mobilstart.se</a>
        </td>
      </tr>
      
	<script language="javascript">
	  function showPass(e){
	      document.getElementById('pushit_sms_service_password').setAttribute('type', e.checked?'text':'password');
		  document.getElementById('pushit_sms_service_password').style.width = e.checked?'460px':'450px';
	  }
	</script>
	
      <tr>
        <th scope="row" align="right"><?php _e('mobilstart.se Password:') ?></th>
        <td>
          <input name="pushit_sms_service_password" type="password" id="pushit_sms_service_password" style="width: 450px" value="<?php form_option('pushit_sms_service_password'); ?>" />
		  <input type="checkbox" onclick="showPass(this)" id="pushit_showinput" /><label for="pushit_showinput"> <?php _e('Show password'); ?></label>
        </td>
      </tr>
	<script language="javascript">
	  function showMSRegIframe(d){
	      document.getElementById('mobilstart_reg_iframe').style.display = d;
	  }
	</script>
      <tr>
        <th scope="row" align="right">&nbsp;</th>
        <td>
          <a href="http://mobilstart.se/" target="_blank">What is mobilstart</a> | <a href="javascript:showMSRegIframe('');">Create an account</a>
        </td>
      </tr>
      <tr>
        <th scope="row" align="right" valign="top"><?php _e('mobilForward order key:') ?></th>
        <td>
          <input name="pushit_mobilforward_order_key" type="text" id="pushit_mobilforward_order_key" style="width: 460px" value="<?php form_option('pushit_mobilforward_order_key'); ?>" /><br />
<?php 
  $wp_url = get_bloginfo( 'wpurl', 'display' );
  $plugin_url = $wp_url . '/wp-content/plugins/pushit/';
?>		  <div style="width: 460px;">In order to properly configure your <a href="https://mobilstart.telenor.se/group/guest/mobilforward" target="_blank">mobilForward</a> please use this url as Target address:<br><strong><?=$plugin_url?>pushit_rp.php</strong></div>
        </td>
      </tr>
      <tr>
        <th scope="row" align="right"><?php _e('bit.ly Login:') ?></th>
        <td>
          <input name="pushit_bitly_login" type="text" id="pushit_bitly_login" style="width: 460px" value="<?php form_option('pushit_bitly_login'); ?>" /> <a target="_blank" href="http://bit.ly/">bit.ly</a>
        </td>
      </tr>
      <tr>
        <th scope="row" align="right"><?php _e('bit.ly apiKey:') ?></th>
        <td>
          <input name="pushit_bitly_apikey" type="text" id="pushit_bitly_apikey" style="width: 460px" value="<?php form_option('pushit_bitly_apikey'); ?>" />
        </td>
      </tr>
      <tr>
        <th scope="row" align="right">&nbsp;</th>
        <td>
          <a href="http://bit.ly/app/faq" target="_blank">What is bit.ly</a> | <a href="http://bit.ly/account/register?rd=%2F" target="_blank">Create an account</a>
        </td>
      </tr>
      <tr>
	<td colspan="2">
	<div id="mobilstart_reg_iframe" style="display: none">
	  <div align="right"><a href="javascript:showMSRegIframe('none');">Close</a></div>
	  <iframe src="https://mobilstart.telenor.se/c/portal/login" name="Mobilstart registration" width="100%" height="910" align="middle" scrolling="auto" frameborder="0"></iframe>
	  <div align="right"><a href="javascript:showMSRegIframe('none');">Close</a></div>
	</div>
	</td>
      </tr>

			<tr>
        <br/>
        <th scope="row" align="right" valign="top"><?php _e('Disable recommend feature:') ?></th>
				<td>
					<input type="checkbox" name="pushit_disablerecommend" id="pushit_disablerecommend" <?=get_option('pushit_disablerecommend') ? "checked='checked'" : ''?> value="1"  />
					<i>Do not show "Recommend this article" widget after each post</i>
				</td>
			</tr>
      <tr>
        <th scope="row" align="right"><?php _e('Sender name:'); ?></th>
        <td>
          <input name="pushit_sender_name" maxlength="11" type="text" id="pushit_sender_name" style="width: 460px" value="<?php form_option('pushit_sender_name'); ?>" />
        </td>
      </tr>
      <tr>
        <th scope="row" align="right" valign="top"><?php _e('Recommend Message Format:') ?></th>
        <td>
          <textarea name="pushit_msg_format" id="pushit_msg_format" style="width: 460px; height: 8em"><?php stripslashes( form_option('pushit_msg_format')); ?></textarea>
	  <div $id="pushit_msg_format_legend">

            [NAME] &mdash; name of message sender<br/>
            [PHONE] &mdash; mobile number of message sender<br/>
            [URL] &mdash; address of the page being recommended<br/>
            <br/>
          </div>
        </td>
      </tr>
      <tr>
        <th scope="row" align="right" valign="top"><?php _e('Registration message Format:') ?></th>
        <td>
          <textarea name="pushit_registration_format" id="pushit_registration_format" style="width: 460px; height: 8em"><?php stripslashes( form_option('pushit_registration_format')); ?></textarea>
          <div $id="pushit_reminder_format_legend">
            [NAME] &mdash; name of message sender<br/>
            [PASS] &mdash; generated password of the user<br/>
            [BLOG_NAME] &mdash; title of this blog<br/>
            [BLOG_URL] &mdash; URL of this blog<br/>
            <br/>
          </div>
        </td>
      </tr>
      <tr>
        <th scope="row" align="right" valign="top"><?php _e('Password Reminder Format:') ?></th>
        <td>
          <textarea name="pushit_reminder_format" id="pushit_reminder_format" style="width: 460px; height: 8em"><?php stripslashes( form_option('pushit_reminder_format')); ?></textarea>
          <div $id="pushit_reminder_format_legend">
            [NAME] &mdash; name of message sender<br/>
            [PASS] &mdash; new generated password of the user<br/>
            [BLOG_NAME] &mdash; title of this blog<br/>
            [BLOG_URL] &mdash; URL of this blog<br/>
            <br/>
          </div>
        </td>
      </tr>
      
      <tr>
        <th scope="row" align="right" valign="top"><?php _e('User Agreement:') ?></th>
        <td>
          <textarea name="pushit_license_text" id="pushit_license_text" style="width: 460px; height: 20em"><?php stripslashes( form_option('pushit_license_text')); ?></textarea>
        </td>
      </tr>
      <tr>
        <th scope="row" align="right" valign="top">&nbsp;</th>
        <td><a href="http://handshake.se/pushit/" target="_blank"><?php _e('About Pushit plugin') ?></a></td>
      </tr>      
<tr>
               <td colspan="2"><em><br/>Once you've made your settings, you can add Pushit functions to your theme.<br/>Insert the following code to any part of your web site theme:<br/><br/>
               - Small icon that unfolds the Pushit overlay <code>&lt;?php if(function_exists('pushit_tabs')) pushit_tabs() ?&gt;</code><br/>
               - Large area with unfolded Pushit and Beetagg. Normally used <b>after</b> a post <code>&lt;?php pushit(); ?&gt;</code></em></td>
         </tr>	  
    </table>
	
    <p class="submit">
      <input type="submit" name="update" value="<?php _e('Save options &raquo;') ?>" /><br/>
    </p>
  </form>
</div>

<?php
}

/*
prints plugin's tabs at the post 
*/
function pushit_tabs() {
	echo pushit_pushit_control('', true, false);
}

/*
private function to print plugin's tabs at the post 
*/
function pushit_pushit_control( $content='', $force=false, $do_show_beetag = true) {
	//if ( is_feed() || get_option('pushit_disablerecommend') )
	if (!$force && (!is_single() || get_option('pushit_disablerecommend')) )
		return $content;
	                       
	$baseurl = get_option('home').'/wp-content/plugins/pushit';

	$url = get_permalink();
	$title = get_the_title();
	
	$post_id = get_the_ID();
	
	$category_id = get_the_category();
	if (is_array($category_id)) $category_id = $category_id[0];
	$category_id = $category_id->cat_ID;
	
	$is_open = is_page() || is_single();
	
	ob_start();?>
	<div><?php if ($do_show_beetag) : ?>
			<img src="<?php show_beetag(); ?>" style="float:left;">
			<?php endif; ?>
<div style="display: inline-block;">
	<span class="pushit-recommend<?=$is_open?' pushit-expand' : ''?>" id="pushit-recommend-<?=$post_id?>">
	
		<span class="pushit-link-thead">
			<a class="pushit-link" href="#" onclick="pushit_link_wnd(<?=$post_id?>, 'show');return false;">Pushit</a>
		</span>
		<span class="trackit-link-thead">
			<a href="#" onclick="return pushit_trackit(<?=$post_id?>, 'show')">TrackIt</a>
		</span>
		<span class="printit-link-thead"><a href="#" onclick="window.print();return false">Print</a></span>
		<div class="pushit-link-tab-wrap">
			<div class="pushit-message" id="pushit-message-<?=$post_id?>" onclick='if(!hasClass(this, "rotating"))this.style.display="none"'></div>
			<div class="pushit-content">
				<form action="" onsubmit="return pushit_send('<?=$post_id?>')">
				<p>Recommend by SMS or email</p>
				<p>
					<div class="ip">+<input id="pushit_mobile_cc_<?=$post_id?>" onfocus="pushit_input_hint_focus(this)" onblur="pushit_input_hint_blur(this)" type="text" value="" title="Your friend mobile phone country code" style="width: 23px; margin-right: 2px;" /><input id="pushit_mobile_<?=$post_id?>" onfocus="pushit_input_hint_focus(this)" onblur="pushit_input_hint_blur(this)" type="text" value="" title="Your friend mobile phone number" style="width: 155px;" /> <img onclick="pushit_recommend_msg(pushit_messages[0])" src="<?=$baseurl?>/images/q.png" alt="" /></div>
					<div class="ip"><input id="pushit_email_<?=$post_id?>"  onfocus="pushit_input_hint_focus(this)" onblur="pushit_input_hint_blur(this)"  type="text" value="" title="Your friend email address" style="width: 190px;"/> <img onclick="pushit_recommend_msg(pushit_messages[1])" src="<?=$baseurl?>/images/q.png" alt="" /></div>
				</p>
				<!--p class="submit-wrap"><a href="#" onclick="return pushit_send('<?=$post_id?>')" border="0"><img src="<?=$baseurl?>/images/pushit_button_large.png" border="0"></a></p-->
				<input type="image" class="pushit_button_large" src="<?=$baseurl?>/images/pushit_button_large.png" onclick="return pushit_send('<?=$post_id?>')" />
				<div class="ip"><a href="http://mobilstart.se/" target="_blank"><img src="<?=$baseurl?>/images/powered_by_mobilstart.gif"></a></div>
				</form>
			</div>
			<div class="trackit-content">
				<div class="trackit-link-tab-wrap-link">
				<ul>
					<li><a href="<?=get_bloginfo('rss2_url')?>">RSS feed for this site</a></li>
					
					<li><a href="<?=get_category_feed_link($category_id)?>">RSS feed for this topic</a></li>
					<li><a href="<?=get_author_feed_link(get_the_author_ID())?>">RSS feed for this author</a></li>
					<li><a href="<?=get_post_comments_feed_link($id)?>">Follow this discussion</a></li>
				</ul>
				</div>
			</div>
			<div class="pushit-link-socwrap">
			<p class="soclink2">
				<a href="http://www.facebook.com/share.php?u=<?=urlencode($url)?>&amp;ts=<?=urlencode($title)?>" target="_top"><img src="<?=$baseurl?>/images/facebook.gif" width="16" height="16" alt="Facebook" /></a>
				<a href="http://digg.com/submit?phase=2&amp;url=<?=urlencode($url)?>&amp;title=<?=urlencode($title)?>" target="_top"><img src="<?=$baseurl?>/images/digg.gif" width="16" height="16" alt="Digg" /></a>
				<a href="http://www.stumbleupon.com/submit?url=<?=urlencode($url)?>&amp;title=<?=urlencode($title)?>" target="_top"><img src="<?=$baseurl?>/images/stumbleupon.gif" width="16" height="16" alt="StumbleUpon" /></a>
				<a href="http://del.icio.us/post?url=<?=urlencode($url)?>&amp;title=<?=urlencode($title)?>" target="_top"><img src="<?=$baseurl?>/images/delicious.gif" width="16" height="16" alt="Delicious" /></a>
				<a href="http://www.pusha.se/posta?url=<?=urlencode($url)?>&amp;title=<?=urlencode($title)?>" target="_top"><img src="<?=$baseurl?>/images/pusha.gif" width="16" height="16" alt="Pusha" /></a>
				<a href="http://twitter.com/home?status=<?=urlencode($url)?>" target="_top"><img src="<?=$baseurl?>/images/twitter.png" width="16" height="16" alt="Twitter" /></a>
				<a href="http://www.technorati.com/faves?add=<?=urlencode($url)?>" target="_top"><img src="<?=$baseurl?>/images/technorati.gif" width="16" height="16" alt="Technorati" /></a>
				<a rel="nofollow" target="_top" href="http://bloggy.se/home?status=<?=urlencode($title)?>+<?=urlencode($url)?>" title="Bloggy"><img src="<?=$baseurl?>/images/bloggy.png" title="Bloggy" alt="Bloggy" /></a>
				<img src="<?=$baseurl?>/images/q.png" alt="" onclick="pushit_recommend_msg(pushit_messages[2])" />
			</p>
			</div>
			
		</div>
		
		<script type="text/javascript">
			pushit_input_hint_blur($('pushit_mobile_<?=$post_id?>'))
			pushit_input_hint_blur($('pushit_mobile_cc_<?=$post_id?>'))
			pushit_input_hint_blur($('pushit_email_<?=$post_id?>'))
			<?php  if ($is_open): ?>
				pushit_link_wnd(<?=$post_id?>, 'show')
				window.pushit_always_open = true
			<?php endif; ?>
		</script>
	</span>
</div>
</div>
<?php $c = ob_get_clean();
	return $content . $c;
}

/*
probably not needed function
*/
function pushit_on_init($arg) {
	$action = empty($_REQUEST['action']) ? 'login' : $_REQUEST['action'];
	if ( basename($_SERVER['PHP_SELF'])!='wp-login.php'  || $action=='logout') return $arg;
	
//	echo '<script>location.href="'.get_option('home').'/";</script>';
//	die();
}
?>

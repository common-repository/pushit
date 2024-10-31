<?php

$success = true;
$message = '';

require_once 'functions.php';
require '../../../wp-config.php';
nocache_headers();


/*
retrieve short url from bit.ly service
*/
function get_short_url($long_url){
  $api_key =  get_option('pushit_bitly_apikey');
  $bitly_login = get_option('pushit_bitly_login');
  if (!isset($api_key) || !isset($bitly_login) || ($api_key == '') || ($bitly_login == '')) {
    return $long_url;
  }
   global $wpdb;
   $table = $wpdb->prefix.'pushit_bitly_cache';
   $sql= 'select shorturl from '.$table." where url like '".$wpdb->escape($long_url)."'";
   $shorturl_from_cache = $wpdb->get_var($wpdb->prepare($sql));
   if (!isset($shorturl_from_cache) || ($shorturl_from_cache == '')){
  $result = file_get_contents('http://api.bit.ly/shorten?version=2.0.1&longUrl='.urlencode($long_url).'&login='.$bitly_login.'&apiKey='.$api_key);
  preg_match('~.*?"shortUrl": "(.*?)",.*~s', $result, $matches);
  $result = $matches[1];
    $wpdb->query($wpdb->prepare('insert into '.$table.' set url = \''.$wpdb->escape($long_url).'\', shorturl = \''.$wpdb->escape($result).'\''));
  } else {
    $result = $shorturl_from_cache;
  }
  return $result;
}

SMS::setCredentials( get_option( 'pushit_sms_service_login' ), get_option( 'pushit_sms_service_password' ) );

switch( $_REQUEST['action'] )
{
  case 'sms':
    if ( !function_exists( 'curl_init' ) )
    {
      $success = false;
      $message = 'cURL is not enabled at the server. ';
      break;
    }
    
    $to          = trim( $_REQUEST['to'] );
    $post_id     = (int)$_REQUEST['id'];
    
    if ( !validate_mobile( '+'.$to, true ) )
    {
      $success = false;
      $message = 'Check recipient phone number, it must start from + and have 9-12 digits';
      $active_field = 'pushit_sms_to_number';
      break;
    }
    
    if ( $post_id <= 0 )
    {
      $success = false;
      $message = 'Wrong post ID. Refresh your page and try again.';
    }
    
    $user = wp_get_current_user();
    $mobile = get_usermeta($user->ID, 'phone_number');
//	$mobile = $wpdb->get_var( $wpdb->prepare( "SELECT `meta_value` FROM $wpdb->usermeta WHERE `meta_key` = 'phone_number' AND `user_id` = %d", $user->ID ) );
    // user should not have possibility to send sms without its mobile in the database
    if (empty($mobile)){ 
      $success = false;
      $message = 'You have no mobile number in the profile.';
      break;
    }
    $phone_number_activated = get_usermeta($user->ID, 'phone_number_activated');
    if (!isset($phone_number_activated) || ($phone_number_activated != 1)){ // probably user have no previously mobile number entered. He must to activate it.
      $success = false;
      $message = 'UNACTIVATED';
      break;
    }
    
    $msg = build_template( get_option( 'pushit_msg_format' ), array(
      '[URL]' => get_short_url(get_permalink($post_id)) . ' ',
      '[NAME]' => coalesce( $user->display_name, $user->login ),
      '[PHONE]' => "+".get_usermeta($user->ID, 'phone_number_cc').$mobile
      )
    );

    SMS::send( $to,  $msg, $mobile);
    break;
      
      
//////////////////////////////////////////////////////////////////////////////////
// {{{ 
  case 'email':

    $to          = trim( $_REQUEST['to'] );
    $from_name   = trim( $_REQUEST['from_name'] );
    $from_email  = trim( $_REQUEST['from_email'] );
    $post_id     = (int)$_REQUEST['id'];
    
    if ( empty( $to ) )
    {
      $success = false;
      $message = 'Check recipient email';
      $active_field = 'pushit_email';
      break;
    }
    
    if ( $post_id <= 0 )
    {
      $success = false;
      $message = 'Wrong post ID. Refresh your page and try again.';
      break;
    }
    
    $user = wp_get_current_user();
    if( $user->ID )
    {
      $mobile = $wpdb->get_var( $wpdb->prepare( "SELECT `meta_value` FROM $wpdb->usermeta WHERE `meta_key` = 'phone_number' AND `user_id` = %d", $user->ID ) );
      $name = coalesce( $user->display_name, $user->login );
    }
    else
    {
      if ( empty( $from_name ) )
      {
        $success = false;
        $message = 'Type your name';
        $active_field = 'pushit_s_name';
        break;
      }
    
      if ( empty( $from_email ) )
      {
        $success = false;
        $message = 'Type your email';
        $active_field = 'pushit_s_email';
        break;
      }
      
      $mobile = $from_email;
      $name   = $from_name;
    }
    
    $msg = build_template( get_option( 'pushit_msg_format' ), array(
      '[URL]' => get_short_url(get_permalink($post_id)) . ' ',
      '[NAME]' => $name,
      '[PHONE]' => "+".get_usermeta($user->ID, 'phone_number_cc').$mobile
      )
    );

    if ( !wp_mail( $to, 'Post recommendation from ' . $user->display_name, $msg ) )
    {
      $success = false;
      $message = 'Cannot send email. Try again later or contact administrator.';
      break;
    }
    break;
// }}}
      
//////////////////////////////////////////////////////////////////////////////////
case 'email-sms':
	usleep(500);
	$active_field = null; // not used in this case
	if ( !function_exists( 'curl_init' ) ) {
		$success = false; 
		$message = 'cURL is not enabled at the server. ';
		break;	
	}
	
	$email   = isset($_REQUEST['email']) ? trim( $_REQUEST['email'] ) : false;
	$msisdn  = isset($_REQUEST['msisdn']) ? str_replace(' ', ' ', $_REQUEST['msisdn'] ) : false;
	$post_id = (int)$_REQUEST['post_id'];
	
	 if (empty($email) && empty($msisdn)) {
		 $success = false;
		 $message = 'Please specify email or mobile number';
		 break;
	 }
	 
    if (!empty($msisdn) && !validate_mobile( '+'.$msisdn, true ) ) {
		$success = false;
		$message = 'Check recipient phone number, it must have 9-12 digits';
		break;
    }
	
	if ( $post_id <= 0 ) {
		$success = false;
		$message = 'Wrong post ID. Refresh your page and try again.';
		break;
	}
	
	$user = wp_get_current_user();
	if (!$user->ID) {
		$success  = false;
		$message  = "UNAUTHORIZED";
		break;
	}
	
	$mobile = $wpdb->get_var( $wpdb->prepare( "SELECT `meta_value` FROM $wpdb->usermeta WHERE `meta_key` = 'phone_number' AND `user_id` = %d", $user->ID ) );
	// user should not have possibility to send sms without its mobile in the database
	if ($mobile == ''){ 
	  $success = false;
	  $message = 'You have no mobile number in the profile.';
	  break;
	}
	$phone_number_activated = get_usermeta($user->ID, 'phone_number_activated');
	if (!isset($phone_number_activated) || ($phone_number_activated != 1)){ // probably user have no previously mobile number entered. He must to activate it.
	  $success = false;
	  $message = 'UNACTIVATED';
	  break;
	}
	
	$name = coalesce( $user->display_name, $user->login );
	
	$msg = build_template( get_option( 'pushit_msg_format' ), array(
	  '[URL]' => get_short_url(get_permalink($post_id)) . ' ',
	  '[NAME]' => $name,
	  '[PHONE]' => "+".get_usermeta($user->ID, 'phone_number_cc').$mobile
	  )
	);
	
	if (!empty($email)) {
		if ( !wp_mail( $email, 'Post recommendation from ' . $user->display_name, $msg ) ) {
		  $success = false;
		  $message = 'Cannot send email. Try again later or contact administrator.';
		  break;
		}
		$success = true;
		$message = 'Thank You!<br/>Your recommendation was successfully sent to '.$email;
	}

	if (!empty($msisdn)) {
		$smscode = SMS::send( $msisdn,  $msg, $mobile );
		if (!is_numeric($smscode)) {
			$success = false;
			$message = 'Error sending SMS message.<br/>Please contact site administrator.';
			break;
		}
		$success = true;
		$message = 'Thank You!<br/>Your recommendation was successfully sent to +'.$msisdn;
	}
	
	
	break;
//////////////////////////////////////////////////////////////////////////////////

  case 'register':
      if ( !get_option('users_can_register') )
      {
        $success = false;
        $message = 'Registration is turned off for this site';
        break;
      }
      
      $_POST['user_login']   = trim( $_POST['user_login'] );
      
      $_POST['user_email']   = trim( $_POST['user_email'] );
      
      if ( empty( $_POST['user_login'] ) )
      {
        $success = false;
        $message = 'Username cannot be empty';
        $active_field = 'user_login';
        break;
      }
      
      if ( empty( $_POST['user_name'] ) )
      {
        $success = false;
        $message = 'We need your name';
        $active_field = 'user_name';
        break;
      }
      
      if ( empty( $_POST['user_surename'] ) )
      {
        $success = false;
        $message = 'We need your surename';
        $active_field = 'user_surename';
        break;
      }
      
      if ( empty( $_POST['user_email'] ) )
      {
        $success = false;
        $message = 'Please type your email';
        $active_field = 'user_email';
        break;
      }
      
      if ( empty( $_POST['user_mobile'] ) )
      {
        $success = false;
        $message = 'We need your mobile phone';
        $active_field = 'user_mobile';
        break;
      }
      
      if ( empty( $_POST['user_license'] ) )
      {
        $success = false;
        $message = 'You must agree with User Agreement';
        $active_field = 'user_license';
        break;
      }
      
      if ( empty( $_POST['mobilstart_user_license'] ) )
      {
        $success = false;
        $message = 'You must agree with  Mobilstart User Agreement';
        $active_field = 'mobilstart_user_license';
        break;
      }
      
      if ( !validate_mobile( $_POST['user_mobile'] ) )
      {
        $success = false;
        $message = 'Phone number must have 9-12 digits';
        $active_field = 'user_mobile';
        break;
      }
      
      $_POST['user_mobile'] = normalize_mobile( $_POST['user_mobile'] );
      
      $key = $wpdb->get_var( $wpdb->prepare( "SELECT `meta_value` FROM $wpdb->usermeta WHERE `meta_key` = 'phone_number' AND `meta_value` = %s",  $_POST['user_mobile'] ) );
    	if ( !empty($key) )
      {
        $success = false;
        $message = 'This phone number is already in use. Please type another one.';
        $active_field = 'user_mobile';
        break;
      }
      
      require_once('../../../wp-includes/registration.php');

      $user_login = $_POST['user_login'];
      $user_email = $_POST['user_email'];
      
      $errors = new WP_Error();

      $user_login = sanitize_user( $user_login );
      $user_email = apply_filters( 'user_registration_email', $user_email );

      // Check the username
      if ( $user_login == '' )
        $errors->add('empty_username', __('ERROR: Please enter a username.'));
      elseif ( !validate_username( $user_login ) ) {
        $errors->add('invalid_username', __('ERROR: This username is invalid.  Please enter a valid username.'));
        $user_login = '';
      } elseif ( username_exists( $user_login ) )
        $errors->add('username_exists', __('ERROR: This username is already registered, please choose another one.'));

      // Check the e-mail address
      if ($user_email == '') {
        $errors->add('empty_email', __('ERROR: Please type your e-mail address.'));
        $active_field = 'user_email';
      } elseif ( !is_email( $user_email ) ) {
        $errors->add('invalid_email', __('ERROR: The email address is not correct.'));
        $active_field = 'user_email';
        $user_email = '';
      } elseif ( email_exists( $user_email ) )
      {
        $errors->add('email_exists', __('ERROR: This email is already registered, please choose another one.'));
        $active_field = 'user_email';
      }

      do_action('register_post', $user_login, $user_email, $errors);

      $errors = apply_filters( 'registration_errors', $errors );

      if ( $errors->get_error_code() )
      {
        $success = false;
        $message = $errors->get_error_message();
        break;
      }

      $user_pass = wp_generate_password( rand(5, 8), false );
      $user_id = wp_create_user( $user_login, $user_pass, $user_email );
      if ( !$user_id )
      {
        $success = false;
        $message = 'Problems while creating a new user';
        break;
      }
      
      if ( $errors->get_error_code() )
      {
        $success = false;
        $message = $errors->get_error_message();
        break;
      }
      
      
      update_usermeta( $user_id, 'first_name', $_POST['user_name'] );  //save last and first name
      update_usermeta( $user_id, 'last_name',  $_POST['user_surename'] ); 
      
      update_usermeta( $user_id, 'phone_number', $_POST['user_mobile'] ); //save phone number
      
      $msg = sprintf( "Hi %s! Your new password is %s", $user_login, $user_pass );
      SMS::send( $_POST['user_mobile'],  $msg, $_POST['user_mobile'] );
      $message = get_bloginfo('name');
      break;
      
//////////////////////////////////////////////////////////////////////////////////
      
  case 'forgotpass':
    	$errors = new WP_Error();

      $token = trim( $_POST['user_login'] );
      if ( empty( $token ) )
      {
        $success = false;
        $message = 'ERROR: Enter a username';
        $active_field = 'user_login';
        break;
      }
      
      if( is_email( $token ) )
      {
        $user_data = get_user_by_email( $token );
      }
      elseif( validate_mobile( $token ) )
      {
        $user_id = $wpdb->get_var( $wpdb->prepare( "SELECT `user_id` 
            FROM $wpdb->usermeta 
            WHERE `meta_key` = 'phone_number' 
            AND `meta_value` = %s", $token ) );
        if( $user_id <= 0 )
        {
          $success = false;
          $message = 'Cannot find user with such mobile number';
          $active_field = 'user_login';
          break;
        }
        $user_data = get_userdata( $user_id );
      }
      else
      {
        $user_data = get_userdatabylogin( $token );
      }
      
      if ( empty( $user_data ) )
      {
        $success = false;
        $message = 'Cannot find this user';
        $active_field = 'user_login';
        break;
      }
      
    	do_action('lostpassword_post');

    	if ( !$user_data )
      {
        $success = false;
        $message = 'ERROR: Invalid username or e-mail.';
        break;
      }

    	// redefining user_login ensures we return the right case in the email
    	$user_login = $user_data->user_login;
      
      $new_pass = wp_generate_password( rand(5, 8), false ); // Generate something random for a key...

      wp_set_password( $new_pass, $user_data->ID );
      do_action( 'password_reset', $user_data );
      
      $msg = build_template( get_option( 'pushit_reminder_format' ), array(
        '[BLOG_NAME]' => get_bloginfo('name'),
        '[BLOG_URL]' => get_option('siteurl') . ' ',
        '[NAME]' => coalesce( $user_data->display_name, $user_data->login ),
        '[PASS]' => $new_pass
        )
      );
      
      $sent = 0;
      
      if( !empty( $_POST['way_email'] ) )
      {

        if( !wp_mail( $user_data->user_email, 'Your
 new password', $msg ) )
        {
          $success = false;
          $message = 'Could not send a email with new password. Try to use SMS for that purpose.';
          break;
        }
        $sent += 1;
      }
      
      if( !empty( $_POST['way_sms'] ) )
      {
        $mobile = $wpdb->get_var( $wpdb->prepare( "SELECT `meta_value` FROM $wpdb->usermeta WHERE `meta_key` = 'phone_number' AND `user_id` = %d", $user_data->ID ) );
        
        if( empty( $mobile ) )
        {
          $success = false;
          $message = 'It is seems your mobile number is not in our records. Were you registered? ';
          break;
        }

        if( !validate_mobile( $mobile ) )
        {
          $success = false;
          $message = htmlentities( $mobile ) . ' is incorrect mobile number. Type correct one or use email for password change.';
          break;
        }

        SMS::send( $mobile,  $msg );
        $sent += 2;
      }

      switch( $sent )
      {
        case 1: //email only
          $message = 'A reminder has been sent to your e-mail address';
          break;
        case 2: //sms only
          $message = 'A reminder SMS has been sent to your cell phone';
          break;
        case 3: //email AND sms
          $message = 'A reminder has ben sent to both your e-mail address and your cell phone';
          break;
        default:
        case 0:
          $success = false;
          $message = 'You should choose whether SMS or email as delivery way';
          break;
      }
      break;
      
//////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////
  case 'login':
  
      $token = trim( $_POST['log'] );
      if ( empty( $token ) )
      {
        $success = false;
        $message = 'Enter your user name,<br/>e-mail or phone number';
        $active_field = 'log';
        break;
      }
      
      if( is_email( $token ) )
      {
        $user_data = get_user_by_email( $token );
      }
      elseif( validate_mobile( $token ) )
      {
        $token = normalize_mobile( $token );
        $user_id = $wpdb->get_var( $wpdb->prepare( "SELECT `user_id` 
            FROM $wpdb->usermeta 
            WHERE `meta_key` = 'phone_number' 
            AND `meta_value` = %s", $token ) );
        if( $user_id <= 0 )
        {
          $success = false;
          $message = 'Cannot find user with such mobile number';
          $active_field = 'log';
          break;
        }
        $user_data = get_userdata( $user_id );
      }
      else
      {
        $user_data = get_userdatabylogin( $token );
      }
      
      if ( empty( $user_data ) )
      {
        $success = false;
        $message = 'Cannot find this user';
        $active_field = 'log';
        break;
      }
      
    	if ( !$user_data )
      {
        $success = false;
        $message = 'Invalid username or e-mail or phone';
        break;
      }

      if ( empty( $_POST['pwd'] ) )
      {
        $success = false;
        $message = 'Type your password';
        $active_field = 'pwd';
        break;
      }
      
    	$_POST['log'] = $user_data->user_login; //WP wants this variable
      
      $user = wp_signon( );
      if ( is_wp_error( $user ) )
      {
        $success = false;
        $message = $user->get_error_message();
        break;
      }
      
      $message = $user_data->user_login;
      break;
}

echo ( $success ? 1 : 0 ) . "|" . strip_tags( $message , '<br/>') . "|" . $active_field;
?>

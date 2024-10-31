<?php
class SMS
{
  var $serviceLogin;
  var $servicePassword;
  
  function setCredentials( $login, $password ) {
    $GLOBALS['pushit']['serviceLogin']  = $login;       // Stupid solution, but the quickest
    $GLOBALS['pushit']['servicePassword'] = $password;
    return true;
  }
  
  function send( $to, $message, $name='handshake' ) {
    //$sms_url = 'https://extsms.bozoka.com/bozoka/api/SmsCPost'; // old
    $sms_url = 'https://extsms.bozoka.com/messaging/api/SmsCPost'; // new
    $sendername = str_replace('+', '',$name);
    $sendername_length = strlen($sendername);
    if ($sendername_length > 11) {
      $sendername = substr($sendername, $sendername_length - 11);
    }
    $data= 'msisdn=' . urlencode($to) 
		. '&msg=' . urlencode( $message ) 
		. '&username=' . urlencode($GLOBALS['pushit']['serviceLogin']) 
		. '&userpassword=' . urlencode($GLOBALS['pushit']['servicePassword']) 
		. '&shortnumber=71501&sendername=' . urlencode( $sendername ) ;

    $c=curl_init($sms_url);
    curl_setopt($c,CURLOPT_POST, true);
    curl_setopt($c,CURLOPT_POSTFIELDS, $data);
    curl_setopt($c,CURLOPT_RETURNTRANSFER, true);
    curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($c, CURLOPT_SSL_VERIFYHOST, false);
    $return = curl_exec($c);
    curl_close($c);
    SMS::log( $to, $message, $name.' ('.$return.')' ) ;
    return $return;
  }

  function log( $to, $message, $name='handshake' )
  {
    $log_file = 'sent.log';
    
    if( !is_writable( $log_file ) )
      return false;
    
    $message = str_replace( "\n", " ", $message );
    $message = str_replace( "\r", " ", $message );
    
    $format = "%s\t%s - [%s] (%s)";
    $fp = fopen( $log_file, 'a' );
    if( $fp )
    {
      @fwrite( $fp, sprintf( $format . "\n", date( 'd.m.Y H:i:s' ), $to, $message, $name ) );
      fclose( $fp );
      return true;
    }
    return false;
  }
}

function normalize_mobile( $number )
{
  $number  = str_replace( ' ', '', $number );
  $number  = str_replace( '(', '', $number );
  $number  = str_replace( ')', '', $number );
  $number  = str_replace( '-', '', $number );
  
  return $number;
}
/**
* number - string to validate
* strict - should function perform strict validate string to accept only numbers 
*          with + or if false (default value) just digits
*/
function validate_mobile( $number, $strict = false)
{
  $number = normalize_mobile( $number );
  if (!$strict){
    return preg_match( '/^[\+]{0,1}([[:digit:]]){9,12}/', $number );
  } else {
    return preg_match( '/^\+([\d]){9,12}/', $number );
  }
}

function build_template( $template, $replacement )
{
  foreach( $replacement as $r=>$v )
  {
    $template = str_replace( $r, $v, $template );
  }
  return $template;
}


//Returns the first non-empty value in the list, or an empty line if there are no non-empty values.
function coalesce()
{ 
  for($i=0; $i < func_num_args(); $i++)
  {
    $arg = func_get_arg($i);
    if(!empty($arg))
      return $arg;
  }
  return "";
}

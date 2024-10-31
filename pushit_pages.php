<?php
//$base_url = '/';
require_once('../../../wp-config.php' );
require_once('pushit.php');
nocache_headers();

$wp_url = get_bloginfo( 'wpurl', 'display' );
$plugin_url = $wp_url . '/wp-content/plugins/pushit/';
?>
<html>
<head>
<link rel="stylesheet" href="pushit.css" type="text/css" media="screen" />
<style>
body,html,table,td,p,div, form {
	margin: 0;
	padding: 0;
	font-family: Charcoal, Helvetica, Arial, Sans-Serif;
	font-size:12px;
}
</style>
</head>
<body style="overflow: hidden;">
<div id="ok_message"></div>
<div id="common_msg" class="error">&nbsp;</div>
<div id="pushit_container">
<?php
switch( $_GET['action'] )
{
  case 'update_mobile_phones':
      global $wpdb;
      $res = $wpdb->get_results($wpdb->prepare('SELECT pn.user_id as id, cc.meta_value as cc, pn.meta_value as phone  FROM '.$wpdb->usermeta.' pn 
  left join '.$wpdb->usermeta.' cc on cc.`user_id` = pn.`user_id` and cc.`meta_key` = \'phone_number_cc\'
  WHERE pn.`meta_key` = \'phone_number\'
  and cc.meta_value is null
  order by pn.user_id'));
      require_once ('phoneparser.class.php');
      $parser = new PhoneParser();
      echo "<table>";
      foreach($res as $row){
	echo "<tr>";
	$parser->original_phone = $row->phone;
	if ($parser->parse()){
	  update_usermeta($row->id, 'phone_number_cc', $parser->country_code);
	  update_usermeta($row->id, 'phone_number', $parser->number);
	  update_usermeta($row->id, 'phone_number_all', $parser->country_code.'<-->'.$parser->number);
	  echo "<td>".$row->phone."</td>";
	  echo "<td>ok</td>";
	} else {
	  echo "<td>".$row->phone."</td>";
	  echo "<td>".$parser->error."</td>";
	}
	echo "</tr>";
      }
      echo "</table>";
  break;
  case 'pushit_send_phone_number_activation_code' :
    $user = wp_get_current_user();
    $mobile = get_user_mobile($user->ID);
    if (!isset($mobile) || ($mobile == '')){
      echo '<h3>You have to save your mobile phone number first.</h3>';
      break;
    }
    $current_activation = get_usermeta($user->ID, 'phone_number_activated');
    if ($current_activation == 1){
      echo '<h3>Your mobile phone number already activated.</h3>';
      break;
    }
	$phone_number_activation_code = generate_and_save_phone_activation_code($user->ID);
//echo $phone_number_activation_code;
//break;
    SMS::setCredentials( get_option( 'pushit_sms_service_login' ), get_option( 'pushit_sms_service_password' ));
    $result = SMS::send($mobile, $phone_number_activation_code, get_option('pushit_sender_name'));
    if (is_numeric($result)){
      echo '<h3>New mobile phone activation code was sent to you.</h3>';
    } else {
      echo '<h3>Error sending new mobile phone activation code.</h3>';
      echo $result;
    }
  break;
  case 'mobilstart_license_text':
    echo '<div align="left">'.nl2br(get_option('mobilstart_license_text')).'</div>';
  break;

  case 'pushit_license_text':
    echo '<div align="left">'.nl2br(get_option('pushit_license_text')).'</div>';
  break;

  case 'recommend':
  {
    global $user_identity;
    $user = wp_get_current_user();
    $post = get_post( $_GET['id'] );
//    print_r( $user );
?>
<div class="roundedcornr_box_598514" id="pushit_recommend">
  <div class="roundedcornr_top_598514"><div></div></div>
  <div class="roundedcornr_content_598514">
    <ul class="tabs">
        <li id="pushit_tab1" class="selected" onclick="m.showTab(1)">SMS</li>
        <li id="pushit_tab2" onclick="m.showTab(2)">E-mail</li>
        <li id="pushit_tab3" onclick="m.showTab(3)">Social Web</li>
    </ul><br/>
    <div id="pushit_tab_contents1">

      <?php if( $user->ID > 0 ) : ?>
      <input type="text" class="text" id="pushit_sms_to_number" name="pushit_sms_to_number" value="Recipient mobile number" onfocus="top.window.delHint( this );" onblur="top.window.addHint( this );" /><br/>
      
      <input type="image" src="<?php echo $plugin_url?>images/submit_default.png" name="submit" id="pushit_sms_submit" onclick="pushit_send_sms(); return false;" onmousemove="this.src='<?php echo $plugin_url?>images/submit_over.png'" onmouseout="this.src='<?php echo $plugin_url?>images/submit_default.png'" />

      <?php else : ?>
      <br/>You are not logged in yet.<br/>
      <a onclick="return top.window.GB_relocate(this.href, this.title, 200 )" title="Login" href="/wp-login.php?redirect_to=recommend&id=<?php echo $post->ID ?>">Log in</a> or <a href="/wp-login.php?action=register" onclick="return top.window.GB_relocate(this.href, this.title, 270 )" >register</a>.
      <?php endif; ?>
    </div>
    <div id="pushit_tab_contents2" class="pushit_nodisplay">
      <input type="text" class="text" id="pushit_email" name="pushit_email" value="Recipient email" onfocus="top.window.delHint( this );" onblur="top.window.addHint( this );" /><br/>
      
      <?php if( !$user->ID ) : ?>
      <input type="text" class="text" id="pushit_s_name" name="pushit_s_name" value="Your email" onfocus="top.window.delHint( this );" onblur="top.window.addHint( this );" /><br/>
      <input type="text" class="text" id="pushit_s_email" name="pushit_s_email" value="Your email" onfocus="top.window.delHint( this );" onblur="top.window.addHint( this );" /><br/>

      <?php endif; ?>
      <input type="image" src="<?php echo $plugin_url?>images/submit_default.png" name="submit" id="pushit_email_submit" onclick="pushit_send_email(); return false;" onmousemove="this.src='<?php echo $plugin_url?>images/submit_over.png'" onmouseout="this.src='<?php echo $plugin_url?>images/submit_default.png'" />
    </div>

    <div id="pushit_tab_contents3" class="pushit_nodisplay">
      <table id="links" align="left">
        <tr>
          <td>
            <a href="http://www.pusha.se/posta?url=<?php echo urlencode( get_permalink( $post->ID ) ) ?>&title=<?php echo urlencode(  $post->post_title ) ?>" target="_top">
              <img src="<?php echo $plugin_url?>images/pusha.gif"/> Pusha
            </a>
          </td>
          <td>
            <a href="http://del.icio.us/post?url=<?php echo urlencode( get_permalink( $post->ID ) ) ?>&title=<?php echo urlencode(  $post->post_title ) ?>" target="_top">
              <img src="<?php echo $plugin_url?>images/delicious.gif"/> del.icio.us
            </a>
          </td>
        </tr>
        <tr>
          <td>
            <a href="http://digg.com/submit?phase=2&url=<?php echo urlencode( get_permalink( $post->ID ) ) ?>&title=<?php echo urlencode(  $post->post_title ) ?>" target="_top">
              <img src="<?php echo $plugin_url?>images/digg.gif"/> Digg
            </a>
          </td>
          <td>
            <a href="http://www.stumbleupon.com/submit?url=<?php echo urlencode( get_permalink( $post->ID ) ) ?>&title=<?php echo urlencode(  $post->post_title ) ?>" target="_top">
              <img src="<?php echo $plugin_url?>images/stumbleupon.gif"/> StumbleUpon
            </a>
          </td>
        </tr>
        <tr>
          <td>
            <a href="http://www.google.com/bookmarks/mark?op=edit&bkmk=<?php echo urlencode( get_permalink( $post->ID ) ) ?>&title=<?php echo urlencode(  $post->post_title ) ?>" target="_top">
              <img src="<?php echo $plugin_url?>images/google_bmarks.gif"/> Google Bookmarks
            </a>
          </td>
          <td>
            <a href="http://www.technorati.com/faves?add=<?php echo urlencode( get_permalink( $post->ID ) ) ?>" target="_top">
              <img src="<?php echo $plugin_url?>images/technorati.gif"/> Technorati
            </a>
          </td>
        </tr>
        <tr>
          <td>
            <a href="http://www.facebook.com/share.php?u=<?php echo urlencode( get_permalink( $post->ID ) ) ?>&ts=<?php echo urlencode(  $post->post_title ) ?>" target="_top">
              <img src="<?php echo $plugin_url?>images/facebook.gif"/> Facebook
            </a>
          </td>
          <td>
            <a rel="nofollow" target="_blank" href="http://bloggy.se/home?status=<?php the_title(); ?>+<?php the_permalink() ?>" title="Bloggy"><img src="<?php bloginfo('template_directory'); ?>/images/social/bloggy.png" title="Bloggy" alt="Bloggy" /></a>
          </td>
        </tr>
      </table>
    </div>
    
  </div>
  <div class="roundedcornr_bottom_598514"><div></div></div>
</div>
<?php
  }
  break;
  
  case 'register':
  {
?>
<script>
var cap = top.window.document.getElementById('GB_Caption');
if( cap)
{
  cap.innerHTML = 'REGISTER';
}
</script>
<div class="roundedcornr_box_598514" id="pushit_register">
  <div class="roundedcornr_top_598514"><div></div></div>
  <div class="roundedcornr_content_598514">
    <form name="pushit_registerForm" id="pushit_registerForm" action="#" onsubmit="return false;" method="post">
      <div><input type="text" class="text" id="user_login" name="user_login" value="Desired username" size="20" onfocus="top.window.delHint( this );" onblur="top.window.addHint( this );" /></div>
      
      <div><input type="text" class="text" id="user_name" name="user_name" value="Given name" size="20" onfocus="top.window.delHint( this );" onblur="top.window.addHint( this );" /></div>
      
      <input type="text" class="text" id="user_surename" name="user_surename" value="Surname" size="20" onfocus="top.window.delHint( this );" onblur="top.window.addHint( this );" /><br/>
      
      <input type="text" class="text" id="user_email" name="user_email" value="E-mail" size="20" onfocus="top.window.delHint( this );" onblur="top.window.addHint( this );" /><br/>
      
      <input type="text" class="text" id="user_mobile" name="user_mobile" value="Mobile phone number" size="20" onfocus="top.window.delHint( this );" onblur="top.window.addHint( this );" /><br/>
      
      
      <div><input type="checkbox" id="user_license" name="user_license" value="1" /><a href="<?php echo $plugin_url?>user_agreement.php" target="_blank" id="user_agr_link">User agreement confirmed</a></div>
      <div><input type="checkbox" id="mobilstart_user_license" name="mobilstart_user_license" value="1" /><a href="<?php echo $plugin_url?>mobilstart_user_agreement.php" target="_blank" id="mobilstart_user_agr_link">Mobilstart User agreement</a></div>
			<div><input type="image" src="<?php echo $plugin_url?>images/submit_default.png" name="submit" id="reg_submit" onclick="pushit_register(); return false;" onmousemove="this.src='<?php echo $plugin_url?>images/submit_over.png'" onmouseout="this.src='<?php echo $plugin_url?>images/submit_default.png'" /></div>

      <div class="footer_links">
  	    <a href="/wp-login.php" id="login_link" onclick="return top.window.GB_relocate(this.href, this.title, 200 )" title="LOGIN" ><?=_('Login')?></a>&nbsp;&nbsp;
	      <a href="/wp-login.php?action=lostpassword" id="forgot_link" onclick="return top.window.GB_relocate(this.href, this.title, 200 )" title="FORGOT PASS?" ><?=_('Lost password?')?></a>
      </div>
      
    </form>
  </div>
  <div class="roundedcornr_bottom_598514"><div></div></div>
</div>

<?php
  }
  break;
  
  case 'forgotpass':
  {
?>
<script>
var cap = top.window.document.getElementById('GB_Caption');
if( cap)
{
  cap.innerHTML = 'Forgot Pass?';
  //cap.style.letterSpacing = '0px';
}
</script>

<div class="roundedcornr_box_598514" id="pushit_lostPassword">
  <div class="roundedcornr_top_598514"><div></div></div>
  <div class="roundedcornr_content_598514">
    <form name="pushit_lostPasswordForm" onsubmit="return false;" id="pushit_lostPasswordForm" action="#" method="post">
      <input type="text" class="text" id="user_login" name="user_login" value="User, email or mobile number:" onfocus="top.window.delHint( this );" onblur="top.window.addHint( this );" /><br/>
	  
	  <table border="0" align="center" style="margin: 0 auto 0 auto;">
        <tr>
          <td rowspan="2">
            <strong><?=_('Send via:')?></strong>
          </td>
          <td>
            <input type="checkbox" name="retrive_way_sms" id="retrive_way_sms" value="1" /> <label for="retrive_way_sms">SMS</label>
          </td>
        </tr>
        <tr>
          <td>
            <input type="checkbox" name="retrive_way_email" id="retrive_way_email" value="1" checked="checked" /> <label for="retrive_way_email">E-mail</label>
          </td>
        </tr>
			</table>
			
      <input id="lost_submit" type="image" src="<?=$plugin_url?>images/remind_default.png" name="submit" onclick="pushit_retrievePassword(); return false;" onmousemove="this.src='<?php echo $plugin_url?>images/remind_over.png'" onmouseout="this.src='<?php echo $plugin_url?>images/remind_default.png'" /><br/>
      
      <div class="footer_links">
  	    <a href="/wp-login.php" id="login_link" onclick="return top.window.GB_relocate(this.href, this.title, 200 )" title="LOGIN" ><?=_('Login')?></a>&nbsp;&nbsp;
	      <a href="/wp-login.php?action=register" id="reg_link" onclick="return top.window.GB_relocate(this.href, this.title, 270 )" title="REGISTER" >Register</a>
      </div>
    
    </form>
  </div>
  <div class="roundedcornr_bottom_598514"><div></div></div>
</div>
<?php
  }
  break;
  
  
  case 'login':
  default:
  {
?>
<script>
var cap = top.window.document.getElementById('GB_Caption');
if( cap)
{
  cap.innerHTML = 'Login';
  //cap.style.letterSpacing = '5px';
}
</script>
<div class="roundedcornr_box_598514" id="pushit_login">
  <div class="roundedcornr_top_598514"><div></div></div>
  <div class="roundedcornr_content_598514">
    <form name="pushit_loginForm" onsubmit="return false;" id="pushit_loginForm" action="#" method="post">
        <div><input type="text" class="text" id="log" name="log" value="User, mobile phone or email" size="20" tabindex="7" onfocus="top.window.delHint( this );" onblur="top.window.addHint( this );"   /></div>
      
        <div><input type="password" class="text" id="pwd" name="pwd" value="" size="20" tabindex="8"  onfocus="top.window.delHint( this );" onblur="top.window.addHint( this );"   /></div>
      
      <input type="checkbox" name="rememberme" id="rememberme" value="forever" tabindex="9" /><label for="rememberme" id="rememberme_label">Remember me</label><br />
      
      <div><input id="log_submit" type="image" src="<?php echo $plugin_url?>images/enter_default.png" name="submit" onclick="pushit_login(<?php echo isset( $_GET['redirect_to'] ) ? "'" . $_GET['redirect_to'] . "'" : '' ?>); return false;" onmousemove="this.src='<?php echo $plugin_url?>images/enter_over.png'" onmouseout="this.src='<?php echo $plugin_url?>images/enter_default.png'" /></div>
			
      <div class="footer_links" >
	      <a href="pushit_pages.php?action=register" id="reg_link" onclick="return top.window.GB_relocate(this.href, this.title, 270 )" title="REGISTER" >Register</a>&nbsp;&nbsp;
  	    <a href="pushit_pages.php?action=forgotpass" id="forgot_link" onclick="return top.window.GB_relocate(this.href, this.title, 200 )" title="FORGOT PASS?" ><?=_('Lost password?')?></a>
      </div>
    </form>
  </div>
  <div class="roundedcornr_bottom_598514"><div></div></div>
</div>
<?php
  }
  
}
?>
<script src="pushit.js"></script>
<script>
//top.window.AJS.addEventListener( top.document.getElementById('GB_window'), 'keypress', keyPressHandler );
//alert( top.window.AJS.listeners );
</script>
</div>

</body>
</html>

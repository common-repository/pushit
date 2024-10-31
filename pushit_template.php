<?php
//  include_once("beetag.php");
  $wp_url = get_bloginfo( 'wpurl', 'display' );
  $backend_url = $wp_url . '/wp-content/plugins/pushit/pushit_pages.php?action=';
?>
<div id="pushit_wrapper">

  <div id="pushit_login" <?php echo ( empty( $user_identity ) ? '' : 'style="display: none"' ) ?>>
    <a href="/wp-login.php" title="LOGIN" rel="gb_page_center[200, 200]">Sign in</a> |
    <a href="/wp-login.php?action=register" title="REGISTER" rel="gb_page_center[200, 270]">Register</a>
  </div>

  <div id="pushit_logged" <?php echo ( empty( $user_identity ) ? 'style="display: none"' : '' ) ?>>
    <strong id="pushit_username"><a href="/wp-admin/profile.php"><?php echo $user_identity ?></a></strong>
    [ <a href="<?php echo wp_nonce_url( site_url('wp-login.php?action=logout', 'login'), 'log-out' ); ?>">Logout</a> ]
  </div>
</div>

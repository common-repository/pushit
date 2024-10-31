<?php
  $dir = getcwd();
  
  if (strpos($dir, '\\')!=FALSE) {
    $pd = '\\';
  } else {
	$pd = '/';
  }
  $dir = str_replace('\\',$pd,$dir);
  
  $dir = str_replace('wp-content'.$pd.'plugins'.$pd.'pushit','',$dir);
  
  $wp_login_fname = $dir.'wp-login.php';
  
  if (!is_writable($wp_login_fname)){
    echo ("File `".$wp_login_fname."` must be writable<br>");
  } else {
    echo $wp_login_fname.' is ok<br>';
  }
  $wp_admin_tpl_fname = $dir."wp-admin/includes/template.php";
  if (!is_writable($wp_admin_tpl_fname)){
    echo("File `".$wp_admin_tpl_fname."` must be writable<br>");
  } else {
    echo $wp_admin_tpl_fname." is ok<br>";
  }
  $wp_user_edit_fname = $dir."wp-admin/user-edit.php";
  if (!is_writable($wp_user_edit_fname)){
    echo("File `".$wp_user_edit_fname."` must be writable<br>");
  } else {
    echo $wp_user_edit_fname." is ok<br>";
  }
  $wp_user_new_fname = $dir."wp-admin/user-new.php";
  if (!is_writable($wp_user_new_fname)){
    echo "File `".$wp_user_new_fname."` must be writable<br>" ;
  } else {
    echo $wp_user_new_fname.' is ok';
  }

?>
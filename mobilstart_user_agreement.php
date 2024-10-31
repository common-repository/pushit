<?php require '../../../wp-config.php' ?>
<html>
<head>
  <title>User Agreement</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <style>
	body,html,table,td,p,div, form {
		margin: 0;
		padding: 0;
		font-family: Charcoal, Helvetica, Arial, Sans-Serif;
	}  	
	#wrapper {
		text-align: center;
		width: 100%;
	}
	#content {
		width: 508px;
		text-align: left;
		margin: 0 auto 0 auto;
	}
  </style>
</head>
<body>
<div id="wrapper">
	<div id="content">
		<?php echo nl2br( get_option('mobilstart_license_text') )?>
	</div>
</div>
</body>
</html>
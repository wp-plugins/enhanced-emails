<!DOCTYPE html>
<html>
<head>
	<title></title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>

<body>
	<div style="width:650px;margin:0 30px 15px 30px;color:#000000;font-family:Arial;font-size:12px;">
    	<a href="#" style="border:0;"><img src="<?php echo eemails_get_logo_url(); ?>" alt="WordPress" style="margin:15px;" /></a>
		<div style="padding: 30px; margin: 0; background-color: #f5f5f5; border: 1px solid #ececec; -webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px;">
		<?php if ( $include_header ) include( 'header.php' ); ?>
            <div style="line-height:170%;padding: 30px; margin: 15px 0 15px 0;background-color: #ffffff; border: 1px solid #ececec; -webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px;">
            	<?php echo $message; ?>
            </div>
        <?php if ( ! empty( $action_links ) ) include( 'action_links.php' ); ?>
        <?php if ( $include_footer ) include( 'footer.php' ); ?>
    	</div>
	</div>
	</body>
</html>
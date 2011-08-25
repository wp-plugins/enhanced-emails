<?php
$html_email = '<!DOCTYPE html>
<html>
<head>
	<title></title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="viewport" content="width=device-width,user-scalable=no" />
	<style type="text/css">		
	@media only screen and (max-device-width: 320px) {
		#EemailsMainContainer {
			width:320px !important;
			margin:0 5px 5px 5px !important;
		}
		#EemailsWpLogo {
			margin:10px !important;
		}
		#EemailsInnerContainer {
			padding:10px !important;
		}
		#EemailsContent {
			padding:10px !important;
		}
		#EemailsMainContainer {
			font-size:11px !important;
		}
	}	
	@media only screen and (min-device-width: 320px) and (max-device-width: 1000px) {
		#EemailsMainContainer {
			width:auto !important;
			margin:0 10px 10px 10px !important;
		}
	}	
	</style>
</head>
<body>
	<div style="max-width:650px;margin:0 0.7em 1em 0.7em;color:#000000;font-family:Arial;font-size:12px;" id="EemailsMainContainer">
    	<a href="' . site_url() . '" style="border:0;"><img src="' . eemails_get_logo_url() . '" alt="WordPress" style="margin:1em;border:0" id="EemailsWpLogo" /></a>
		<div style="padding: 2em; margin: 0; background-color: #f5f5f5; border: 1px solid #ececec; -webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px;" id="EemailsInnerContainer">';
		if ( $include_header ) include( 'header.php' );
        include( $template );
        if ( $include_footer ) include( 'footer.php' );
$html_email .= '
		</div>
	</div>
	</body>
</html>';
?>
           
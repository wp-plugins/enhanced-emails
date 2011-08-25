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
            <?php if ( 'comment' == $comment_type ) { ?>
            <table style="margin-top:15px;">
            	<tr>
                	<td style="width:75px;" width="75" rowspan="3">
                    <div style="width:60px;height:60px;background-color: #ffffff; border: 1px solid #ececec; -webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px;">
                 		<a style="margin:5px 0 0 5px;display:block;"><?php echo eemails_get_gravatar($comment->comment_author_email); ?></a>
                    </div></td>
                    <td height="12" style="height:12px;padding-top:2px;">Author: <strong><?php echo $comment->comment_author; ?></strong></td>
                </tr>
                <tr><td style="font-size:10px;padding-left:45px;vertical-align:top">IP: <?php echo $comment->comment_author_IP; ?><?php if ( $comment->comment_author_domain ) { ?>, <a href="#" style="color:#21759b;text-decoration:none;"><?php echo $comment->comment_author_domain; ?></a><?php } ?></td></tr>
                <tr><td>Email: <a href="mailto:<?php echo $comment->comment_author_email; ?>" style="color:#21759b;text-decoration:none;"><?php echo $comment->comment_author_email; ?></a></td></tr>
            </table>
            <table style="margin-top:15px;">
                <tr><td style="line-height:150%;">URL: <a href="<?php echo $comment->comment_author_url; ?>" style="color:#21759b;text-decoration:none;"><?php echo $comment->comment_author_url; ?></a></td></tr>
                <tr><td style="line-height:150%;">Whois: <a href="http://whois.arin.net/rest/ip/<?php echo $comment->comment_author_IP; ?>" style="color:#21759b;text-decoration:none;">http://whois.arin.net/rest/ip/<?php echo $comment->comment_author_IP; ?></a></td></tr>
            </table>
            <?php } elseif ( 'trackback' == $comment_type ) { ?>
            <table style="margin-top:15px;">
                <tr><td>Website: <a href="#" style="color:#21759b;text-decoration:none;"><?php echo $comment->comment_author; ?></a></td></tr>
                <tr><td style="font-size:10px;padding-left:45px;vertical-align:top">IP: <?php echo $comment->comment_author_IP; ?><?php if ( $comment->comment_author_domain ) { ?>, <a href="#" style="color:#21759b;text-decoration:none;"><?php echo $comment->comment_author_domain; ?></a><?php } ?></td></tr>
            </table>
            <table style="margin-top:15px;">
                <tr><td style="line-height:150%;">URL: <a href="#" style="color:#21759b;text-decoration:none;"><?php echo $comment->comment_author_url; ?></a></td></tr>
            </table>
            <?php } elseif ( 'pingback' == $comment_type ) { ?>
            <table style="margin-top:15px;">
                <tr><td>Website: <a href="#" style="color:#21759b;text-decoration:none;"><?php echo $comment->comment_author; ?></a></td></tr>
                <tr><td style="font-size:10px;padding-left:45px;vertical-align:top">IP: <?php echo $comment->comment_author_IP; ?><?php if ( $comment->comment_author_domain ) { ?>, <a href="#" style="color:#21759b;text-decoration:none;"><?php echo $comment->comment_author_domain; ?></a><?php } ?></td></tr>
            </table>
            <table style="margin-top:15px;">
                <tr><td style="line-height:150%;">URL: <a href="#" style="color:#21759b;text-decoration:none;"><?php echo $comment->comment_author_url; ?></a></td></tr>
            </table>
            <?php } ?>
            <div style="line-height:170%;padding: 30px; margin: 15px 0 15px 0;background-color: #ffffff; border: 1px solid #ececec; -webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px;">
            	<?php 
				if ( 'pingback' == $comment_type ) echo '[...] ';
				echo $comment->comment_content;
				if ( 'pingback' == $comment_type ) echo ' [...]';
				?>
            </div>
            <table>
                <tr><td style="line-height:150%;">Context: <a href="#" style="color:#21759b;text-decoration:none;"><?php echo get_permalink( $comment->comment_post_ID ); ?></a></td></tr>
                <!-- Take action: <a href="#" style="color:#3ca757;text-decoration:none;margin: 0 10px;font-weight:bold; font-size: 14px">Approve</a> | <a href="#" style="color:#d2b12e;text-decoration:none;margin: 0 10px;font-weight:bold; font-size: 14px">Mark as spam</a> | <a href="#" style="color:#c23031;text-decoration:none;margin: 0 10px;font-weight:bold; font-size: 14px">Trash</a> -->
                <tr><td style="line-height:200%;"><?php eemails_action_links( $action_links, $action_label ); ?></td></tr>
                <?php if ( isset( $moderation_message ) ) { ?>
                <tr><td style="line-height:150%;"><?php echo $moderation_message; ?></td></tr>
                <?php } ?>
            </table>
            <?php if ( $include_footer ) include( 'footer.php' ); ?>
    	</div>
	</div>
	</body>
</html>
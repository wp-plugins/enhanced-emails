<?php 
			if ( 'comment' == $comment_type ) { 
            $html_email .= '<table style="margin-top:1em;">
            	<tr>
                	<td style="width:75px;" width="75" rowspan="3">
                    <div style="width:60px;height:60px;background-color: #ffffff; border: 1px solid #ececec; -webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px;">
                 		<a style="margin:5px 0 0 5px;display:block;">' . eemails_get_gravatar($comment->comment_author_email) . '</a>
                    </div></td>
                    <td height="12" style="height:12px;padding-top:2px;">Author: <strong>' . $comment->comment_author . '</strong></td>
                </tr>
                <tr><td style="font-size:10px;padding-left:45px;vertical-align:top">IP: ' . $comment->comment_author_IP; 
			if ( $comment->comment_author_domain ) { $html_email .= ', <a href="#" style="color:#21759b;text-decoration:none;">' . $comment->comment_author_domain . '</a>'; } 
            $html_email .= '</td></tr>
                <tr><td>Email: <a href="mailto:' . $comment->comment_author_email . '" style="color:#21759b;text-decoration:none;">' . $comment->comment_author_email . '</a></td></tr>
            </table>
            <table style="margin-top:15px;">
                <tr><td style="line-height:150%;">URL: <a href="' . $comment->comment_author_url . '" style="color:#21759b;text-decoration:none;">' . $comment->comment_author_url . '</a></td></tr>
                <tr><td style="line-height:150%;">Whois: <a href="http://whois.arin.net/rest/ip/' . $comment->comment_author_IP . '" style="color:#21759b;text-decoration:none;">http://whois.arin.net/rest/ip/' . $comment->comment_author_IP . '</a></td></tr>
            </table>';
            } elseif ( 'trackback' == $comment_type ) { 
            $html_email .= '<table style="margin-top:15px;">
                <tr><td>Website: <a href="' . $comment->comment_author . '" style="color:#21759b;text-decoration:none;">' . $comment->comment_author . '</a></td></tr>
                <tr><td style="font-size:10px;padding-left:45px;vertical-align:top">IP: ' . $comment->comment_author_IP; 
			if ( $comment->comment_author_domain ) { $html_email .= ', <a href="' . $comment->comment_author_domain . '" style="color:#21759b;text-decoration:none;">' . $comment->comment_author_domain . '</a>';}
			$html_email .= '</td></tr>
            </table>
            <table style="margin-top:15px;">
                <tr><td style="line-height:150%;">URL: <a href="' . $comment->comment_author_url . '" style="color:#21759b;text-decoration:none;">' . $comment->comment_author_url . '</a></td></tr>
            </table>';
            } elseif ( 'pingback' == $comment_type ) { 
            $html_email .= '<table style="margin-top:15px;">
                <tr><td>Website: <a href="' . $comment->comment_author . '" style="color:#21759b;text-decoration:none;">' . $comment->comment_author . '</a></td></tr>
                <tr><td style="font-size:10px;padding-left:45px;vertical-align:top">IP: ' . $comment->comment_author_IP;
			if ( $comment->comment_author_domain ) { $html_email .= ', <a href="#" style="color:#21759b;text-decoration:none;">' . $comment->comment_author_domain .'</a><';}
			$html_email .= '</td></tr>
            </table>
            <table style="margin-top:15px;">
                <tr><td style="line-height:150%;">URL: <a href="' . $comment->comment_author_url . '" style="color:#21759b;text-decoration:none;">' . $comment->comment_author_url . '</a></td></tr>
            </table>';
            } 
            $html_email .= '<div style="line-height:170%;padding: 2em; margin: 1em 0 1em 0;background-color: #ffffff; border: 1px solid #ececec; -webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px;" id="EemailsContent">';
			if ( 'pingback' == $comment_type ) $html_email .= '[...] ';
			$html_email .= wp_specialchars_decode( $comment->comment_content );
			if ( 'pingback' == $comment_type ) $html_email .= ' [...]';
           $html_email .= ' </div>
            <table>
                <tr><td style="line-height:150%;">Context: <a href="' . get_permalink( $comment->comment_post_ID ) . '" style="color:#21759b;text-decoration:none;">' . get_permalink( $comment->comment_post_ID ) . '</a></td></tr>';
           if ( isset( $moderation_message ) ) {
                $html_email .= ' <tr><td style="line-height:150%;">' . $moderation_message . '</td></tr>';
            }
            $html_email .= ' </table>';
            if ( ! empty( $action_links ) ) include( 'action_links.php' );
?>
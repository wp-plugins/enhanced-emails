<?php
			$html_email .= '<div style="line-height:170%;padding: 2em; margin: 1em 0 1em 0;background-color: #ffffff; border: 1px solid #ececec; -webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px;" id="EemailsContent">
                <p style="margin:2em 0 0.8em 0">A new user - <strong>' . $user->display_name . '</strong>, has just registered on your site.</p>
                <p style="margin:2em 0 0.8em 0">
                Username: <strong>' . $user->user_login . '</strong><br />
                Email: <strong style="text-decoration:none !important;">' . $user->user_email . '</strong><br /></p>
            </div>';
            if ( ! empty( $action_links ) ) include( 'action_links.php' );
?>
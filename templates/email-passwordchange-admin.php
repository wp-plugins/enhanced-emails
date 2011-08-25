<?php
			$html_email .= '<div style="line-height:170%;padding: 30px; margin: 15px 0 15px 0;background-color: #ffffff; border: 1px solid #ececec; -webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px;" id="EemailsContent">
                <p style="margin:2em 0 0.8em 0">The password was reset and changed for the following user:</p>
                <p style="margin:2em 0 0.8em 0">
                Username: <strong>' . $user->user_login . '</strong><br />
                Email: <strong>' . $user->user_email . '</strong><br /></p>
            </div>';
            if ( ! empty( $action_links ) ) include( 'action_links.php' );
?>
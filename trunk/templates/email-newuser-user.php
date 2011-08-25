<?php
			$html_email .= '<div style="line-height:170%;padding: 2em; margin: 1em 0 1em 0;background-color: #ffffff; border: 1px solid #ececec; -webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px;" id="EemailsContent">
            	Howdy, ' . $user->user_login . '!
                <p style="margin:30px 0 10 0">Your account has been successfully created and is ready to use!</p>
                <p style="margin:30px 0 10 0">You can log in to your account with the following information:<br />
                Username: <strong>' . $user->user_login . '</strong><br />
                Password: <strong>' . $user_password . '</strong><br /></p>
            </div>';
            if ( ! empty( $action_links ) ) include( 'action_links.php' );
?>
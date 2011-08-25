<?php
            $html_email .= '<div style="line-height:170%;padding: 2em; margin: 1em 0 1em 0;background-color: #ffffff; border: 1px solid #ececec; -webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px;" id="EemailsContent">
            	Your new WordPress blog has been successfully set up at:<br />
                <a href="' . site_url() . '" style="color:#21759b;text-decoration:none;">' . site_url() . '</a>
                <p style="margin:2em 0 0 0">You can log in to the administrator account with the following information:<br />
                Username: <strong>admin</strong><br />
                Password: the password you chose during the install</p>
            </div>';
            if ( ! empty( $action_links ) ) include( 'action_links.php' ); 
        	$html_email .= '<p style="margin-top:15px;line-height:170%;">We hope you enjoy your new blog. Thanks! <br />';
?>
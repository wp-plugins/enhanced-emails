<?php
/*
Plugin Name: Enhanced Emails
Plugin URI: http://wojtekszkutnik.com
Description: The Enhanced Emails plugin adds e-mail themes, HTML versions for custom and default e-mails and easier use of enriched e-mails out of the box.
Version: 0.2.1
Author: Wojtek Szkutnik
Author URI: http://wojtekszkutnik.com
License: GPL2
*/

/*  Copyright 2011  Wojtek Szkutnik  (email : me@wojtekszkutnik.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

include('pluggable.php');

define( 'EEMAILS_DESCENDING_HIERARCHY', true ); // Descending hierarchy means that for emails-function-name emails-function-name.php, emails-function.php, emails.php will be tried.
												// Otherwise, only the first and last option will be tried (emails-function-name.php and after that, emails.php)

add_action('personal_options', 'eemails_setting_use_html');
add_action('personal_options_update', 'eemails_save_setting_use_html');
add_action('admin_init', 'eemails_admin_init');

/*
	User / admin settings
*/

function eemails_admin_init() {
	register_setting( 'general', 'eemails_use_html', 'intval' );
	add_settings_field(
		'eemails_use_html',      			 // id
		'Use HTML emails',              	 // setting title
		'eemails_setting_use_html_admin',    // display callback
		'general',                 			 // settings page
		'default'                  			 // settings section
	);
}

function eemails_setting_use_html_admin() {
	$value = get_option( 'eemails_use_html' );
	// echo the field
	?>
 <input id='eemails_use_html' name='eemails_use_html' type='checkbox' value="1" <?php checked('1', $value); ?> /> Use HTML emails
	<?php
}

function eemails_setting_use_html($user) {
?>
    <tr>
        <th scope="row">Send me HTML e-mails</th>
        <td><label for="eemails_html">
        <input name="eemails_html" type="checkbox" id="eemails_html" value="1" <?php checked('1', $user->eemails_html); ?> /> Use HTML e-mails for all messages and notifications.
        </label></td>
    </tr>
<?php
}

function eemails_save_setting_use_html( $user_id ) {
    $eemails_html = ( !empty($_POST['eemails_html']) ? 1 : 0 );
    update_usermeta( $user_id, 'eemails_html', $eemails_html );
}

function eemails_get_template( $slug, $event = null , $name = null ) {
        do_action( "get_email_template_{$slug}", $slug, $event, $name );
        $directories = apply_filters( 'email-template-directories', array(
                '', // Blank is the template directories, which we want to check first
                dirname( __FILE__ ) . '/templates/', // The templates included with the plugin
        ) );
        $templates = array();
        foreach ( $directories as $dir ) {
                $dir_slug = empty( $dir )? $slug : path_join( $dir, $slug );

                if ( isset( $event ) && isset( $name ) )
                        $templates[] = "{$dir_slug}-{$event}-{$name}.php";

                if ( isset( $event ) )
                        $templates[] = "{$dir_slug}-{$event}.php";

                $templates[] = "{$dir_slug}.php";
        }

        return locate_email_template( $templates );
}

function locate_email_template( $template_names ) {
        $located = '';
        foreach ( (array) $template_names as $template_name ) {
                if ( !$template_name )
                        continue;
                if ( file_exists( get_stylesheet_directory() . '/' . $template_name ) ) {
                        $located = get_stylesheet_directory() . '/' . $template_name;
                        break;
                } else if ( file_exists( get_template_directory() . '/' . $template_name ) ) {
                        $located = get_template_directory() . '/' . $template_name;
                        break;
                } else if ( file_exists( $template_name ) ) {
                        $located = $template_name;
                        break;
                }
        }

        return $located;
}

/*
	E-mail handling
*/

function eemails_charset( $charset = '' ) {
	// If the charset is not set (most of the times) get the global blog setting
	if ( '' == $charset )
		$charset = get_option('blog_charset');

	return "Content-Type: text/html; charset=\"{$charset}\"\n";
}


function eemails_wp_mail( $to, $subject, $message, $headers = '', $attachments = array(), $args = array() ) {
	$defaults = array (
 		'template' => 'email',
 		'template_args' => array(),
 		'content_title' => '',
 		'action_links' => array(),
 		'include_header' => true,
 		'include_footer' => true,
	);
	$args = wp_parse_args( $args, $defaults );

	if ( ! $content_title )
		$content_title = $subject;

	extract( $args, EXTR_SKIP );
	extract( $template_args, EXTR_SKIP );

	$template = eemails_get_template($args['template-slug'], $args['template-event'], $args['template-name']);
	$html_email = '';
	include( 'templates/layout.php' );
	$message = array(
					 'text/plain' => $message,
					 'text/html' => $html_email
					);

	return wp_mail( $to, $subject, $message );
}

/*
	Utils
*/

function eemails_get_image_url( $image_name ) {
	return plugins_url( '/templates/images/' . $image_name, __FILE__ );
}

function eemails_get_logo_url() {
	$email_logo = eemails_get_image_url( 'wp_logo.jpg' );
	return apply_filters( 'eemails_html_logo', $email_logo );
}

function eemails_get_action_links( $action_links = array(), $label = 'Take action:' ) {
	$links = array();

	foreach ($action_links as $link) {
		$links[] = '<a href="' . $link['link'] . '" style="color:#' . $link['color'] . ';text-decoration:none;margin: 0 10px;font-weight:bold; font-size: 14px">' . $link['text'] . '</a>';
	}

	if ( ! empty ( $links ) )
		$links = $label . implode(' | ',$links);

	return $links;
}

function eemails_test() {
	wp_notify_postauthor(2);
	wp_notify_moderator(2);
	wp_new_user_notification(1, 'mysecretpassword');
	wp_password_change_notification(new WP_User(1));
}
?>
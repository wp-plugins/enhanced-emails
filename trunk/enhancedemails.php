<?php
/*
Plugin Name: Enhanced Emails
Plugin URI: http://wojtekszkutnik.com
Description: The enhanced emails plugin allows users to send html-enriched emails and use email themes.
Version: 0.1
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

define( 'EEMAILS_PATH', dirname( __FILE__ ) );
define( 'EEMAILS_FOLDER_NAME', '/templates/' );
define( 'EEMAILS_TEMPLATES_PATH', EEMAILS_PATH . EEMAILS_FOLDER_NAME );
define( 'EEMAILS_FOLDER', dirname( plugin_basename( __FILE__ ) ) );
define( 'EEMAILS_URL', plugins_url( EEMAILS_FOLDER ) );
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
 <input id='eemails_use_html' name='eemails_use_html'
 type='checkbox' value="1" <?php checked('1', $value); ?>' /> Use HTML emails
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

/*
	Template hierarchy
*/

function eemails_get_template( $template_name ) {
	
	// Try the theme template dirs
	$template_file = '';#eemails_get_template_from_dir( $template_name );
	
	// Then the wp content dir
	if ( 0 && ! $template_file )
		$template_file = eemails_get_template_from_dir( $template_name, WP_CONTENT_DIR );
		
	// Then the default plugin templates folder
	if ( ! $template_file )
		$template_file = eemails_get_template_from_dir( $template_name, EEMAILS_TEMPLATES_PATH );
		
	return $template_file;
}

function eemails_get_template_from_dir( $template_name, $dir = false ) {
	
	// Split the template name into module names
	$name_parts = explode( '-', $template_name );
	$name_parts_index  = count( $name_parts )-1;
	$template_match = '';
	
	// Look for the best match
	while ( $name_parts_index >= 0 ) {
		
		$template_file_name =  implode( '-', $name_parts ) . '.php';
		$template_name_temp = trailingslashit( $dir ) . $template_file_name;

		// If the directory name is not set, search via locate_template
		if ( false === $dir ) {
			$template_match = locate_template( $template_file_name );
			if ( $template_match )
				break;
		}
		
		// If the template file exists - it is the best match
		if( file_exists( $template_name_temp ) ) {
			$template_match = $template_name_temp;
			break;
		}
		
		// If the template does not exist, search for a more generic template
		unset( $name_parts[$name_parts_index] );
		$name_parts_index--;
		 
		if ( ( ! EEMAILS_DESCENDING_HIERARCHY ) && ( $name_parts_index > 0 ) ) {
			$name_parts = Array($name_parts[0]);
			$name_parts_index = 0;
		}
	}
	
	return $template_match;
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


function eemails_wp_mail( $to, $subject, $message, $args = array() ) {
	$defaults = array (
		'headers' => '',
		'attachments' => array(),
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
	
	$template = eemails_get_template($template);
	
	ob_start();
	@include( $template );
	$message = array( 
					 'text/plain' => $message,
					 'text/html' =>ob_get_contents()
					);
	ob_end_clean();
	
	return wp_mail( $to, $subject, $message );
}

/*
	Utils
*/

function eemails_get_image_url( $image_name ) {
	return EEMAILS_URL . EEMAILS_FOLDER_NAME . 'images/' . $image_name;
}

function eemails_get_logo_url() {
	$email_logo = eemails_get_image_url( 'wp_logo.jpg' );
	return apply_filters( 'eemails_html_logo', $email_logo );
}

function eemails_get_gravatar( $email, $size = 50 ) {
	$gravatar = get_avatar( $email, $size );
	return $gravatar;
}


function eemails_blogname( ) {
	echo wp_specialchars_decode( get_option('blogname'), ENT_QUOTES );
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

function eemails_action_links( $action_links = array(), $label = '' ) {
	echo eemails_get_action_links( $action_links, $label );
}

function eemails_test() {
	wp_notify_postauthor(2);
	wp_notify_moderator(2);
	wp_new_user_notification(1, 'mysecretpassword');
	wp_password_change_notification(new WP_User(1));	
}
?>
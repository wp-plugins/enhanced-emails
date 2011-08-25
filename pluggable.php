<?php

if ( !function_exists( 'wp_mail' ) ) :
/**
 * Send mail, similar to PHP's mail
 *
 * A true return value does not automatically mean that the user received the
 * email successfully. It just only means that the method used was able to
 * process the request without any errors.
 *
 * Using the two 'wp_mail_from' and 'wp_mail_from_name' hooks allow from
 * creating a from address like 'Name <email@address.com>' when both are set. If
 * just 'wp_mail_from' is set, then just the email address will be used with no
 * name.
 *
 * The default content type is 'text/plain' which does not allow using HTML.
 * However, you can set the content type of the email by using the
 * 'wp_mail_content_type' filter.
 *
 * If $message is an array, the key of each is used to add as an attachment
 * with the value used as the body. The 'text/plain' element is used as the
 * text version of the body, with the 'text/html' element used as the HTML
 * version of the body. All other types are added as attachments.
 *
 * The default charset is based on the charset used on the blog. The charset can
 * be set using the 'wp_mail_charset' filter.
 *
 * @since 1.2.1
 * @uses apply_filters() Calls 'wp_mail' hook on an array of all of the parameters.
 * @uses apply_filters() Calls 'wp_mail_from' hook to get the from email address.
 * @uses apply_filters() Calls 'wp_mail_from_name' hook to get the from address name.
 * @uses apply_filters() Calls 'wp_mail_content_type' hook to get the email content type.
 * @uses apply_filters() Calls 'wp_mail_charset' hook to get the email charset
 * @uses do_action_ref_array() Calls 'phpmailer_init' hook on the reference to
 *		phpmailer object.
 * @uses PHPMailer
 * @
 *
 * @param string|array $to Array or comma-separated list of email addresses to send message.
 * @param string $subject Email subject
 * @param string|array $message Message contents
 * @param string|array $headers Optional. Additional headers.
 * @param string|array $attachments Optional. Files to attach.
 * @return bool Whether the email contents were sent successfully.
 */
function wp_mail( $to, $subject, $message, $headers = '', $attachments = array() ) {
	// Compact the input, apply the filters, and extract them back out
	extract( apply_filters( 'wp_mail', compact( 'to', 'subject', 'message', 'headers', 'attachments' ) ) );

	if ( !is_array($attachments) )
		$attachments = explode( "\n", str_replace( "\r\n", "\n", $attachments ) );

	global $phpmailer;

	// (Re)create it, if it's gone missing
	if ( !is_object( $phpmailer ) || !is_a( $phpmailer, 'PHPMailer' ) ) {
		require_once ABSPATH . WPINC . '/class-phpmailer.php';
		require_once ABSPATH . WPINC . '/class-smtp.php';
		$phpmailer = new PHPMailer( true );
	}

	// Headers
	if ( empty( $headers ) ) {
		$headers = array();
	} else {
		if ( !is_array( $headers ) ) {
			// Explode the headers out, so this function can take both
			// string headers and an array of headers.
			$tempheaders = explode( "\n", str_replace( "\r\n", "\n", $headers ) );
		} else {
			$tempheaders = $headers;
		}
		$headers = array();
		$cc = array();
		$bcc = array();

		// If it's actually got contents
		if ( !empty( $tempheaders ) ) {
			// Iterate through the raw headers
			foreach ( (array) $tempheaders as $header ) {
				if ( strpos($header, ':') === false ) {
					if ( false !== stripos( $header, 'boundary=' ) ) {
						$parts = preg_split('/boundary=/i', trim( $header ) );
						$boundary = trim( str_replace( array( "'", '"' ), '', $parts[1] ) );
					}
					continue;
				}
				// Explode them out
				list( $name, $content ) = explode( ':', trim( $header ), 2 );

				// Cleanup crew
				$name    = trim( $name    );
				$content = trim( $content );

				switch ( strtolower( $name ) ) {
					// Mainly for legacy -- process a From: header if it's there
					case 'from':
						if ( strpos($content, '<' ) !== false ) {
							// So... making my life hard again?
							$from_name = substr( $content, 0, strpos( $content, '<' ) - 1 );
							$from_name = str_replace( '"', '', $from_name );
							$from_name = trim( $from_name );

							$from_email = substr( $content, strpos( $content, '<' ) + 1 );
							$from_email = str_replace( '>', '', $from_email );
							$from_email = trim( $from_email );
						} else {
							$from_email = trim( $content );
						}
						break;
					case 'content-type':
						if ( is_array($message) ) {
							// Multipart email, ignore the content-type header
							break;
						}
						if ( strpos( $content, ';' ) !== false ) {
							list( $type, $charset ) = explode( ';', $content );
							$content_type = trim( $type );
							if ( false !== stripos( $charset, 'charset=' ) ) {
								$charset = trim( str_replace( array( 'charset=', '"' ), '', $charset ) );
							} elseif ( false !== stripos( $charset, 'boundary=' ) ) {
								$boundary = trim( str_replace( array( 'BOUNDARY=', 'boundary=', '"' ), '', $charset ) );
								$charset = '';
							}
						} else {
							$content_type = trim( $content );
						}
						break;
					case 'cc':
						$cc = array_merge( (array) $cc, explode( ',', $content ) );
						break;
					case 'bcc':
						$bcc = array_merge( (array) $bcc, explode( ',', $content ) );
						break;
					default:
						// Add it to our grand headers array
						$headers[trim( $name )] = trim( $content );
						break;
				}
			}
		}
	}

	// Empty out the values that may be set
	$phpmailer->ClearAddresses();
	$phpmailer->ClearAllRecipients();
	$phpmailer->ClearAttachments();
	$phpmailer->ClearBCCs();
	$phpmailer->ClearCCs();
	$phpmailer->ClearCustomHeaders();
	$phpmailer->ClearReplyTos();

	// From email and name
	// If we don't have a name from the input headers
	if ( !isset( $from_name ) )
		$from_name = 'WordPress';

	/* If we don't have an email from the input headers default to wordpress@$sitename
	 * Some hosts will block outgoing mail from this address if it doesn't exist but
	 * there's no easy alternative. Defaulting to admin_email might appear to be another
	 * option but some hosts may refuse to relay mail from an unknown domain. See
	 * http://trac.wordpress.org/ticket/5007.
	 */

	if ( !isset( $from_email ) ) {
		// Get the site domain and get rid of www.
		$sitename = strtolower( $_SERVER['SERVER_NAME'] );
		if ( substr( $sitename, 0, 4 ) == 'www.' ) {
			$sitename = substr( $sitename, 4 );
		}

		$from_email = 'wordpress@' . $sitename;
	}

	// Plugin authors can override the potentially troublesome default
	$phpmailer->From     = apply_filters( 'wp_mail_from'     , $from_email );
	$phpmailer->FromName = apply_filters( 'wp_mail_from_name', $from_name  );

	// Set destination addresses
	if ( !is_array( $to ) )
		$to = explode( ',', $to );

	foreach ( (array) $to as $recipient ) {
		try {
			// Break $recipient into name and address parts if in the format "Foo <bar@baz.com>"
			$recipient_name = '';
			if( preg_match( '/(.+)\s?<(.+)>/', $recipient, $matches ) ) {
				if ( count( $matches ) == 3 ) {
					$recipient_name = $matches[1];
					$recipient = $matches[2];
				}
			}
			$phpmailer->AddAddress( trim( $recipient ), $recipient_name);
		} catch ( phpmailerException $e ) {
			continue;
		}
	}

	// If we don't have a charset from the input headers
	if ( !isset( $charset ) )
		$charset = get_bloginfo( 'charset' );

	// Set the content-type and charset
	$phpmailer->CharSet = apply_filters( 'wp_mail_charset', $charset );

	// Set mail's subject and body
	$phpmailer->Subject = $subject;

	if ( is_string($message) ) {
		$phpmailer->Body    = $message;

		// Set Content-Type and charset
		// If we don't have a content-type from the input headers
		if ( !isset( $content_type ) )
			$content_type = 'text/plain';

		$content_type = apply_filters( 'wp_mail_content_type', $content_type );

		$phpmailer->ContentType = $content_type;

		// Set whether it's plaintext, depending on $content_type
		if ( 'text/html' == $content_type )
			$phpmailer->IsHTML( true );

		// For backwards compatibility, new multipart emails should use
		// the array style $message. This never really worked well anyway
		if ( false !== stripos( $content_type, 'multipart' ) && ! empty($boundary) )
			$phpmailer->AddCustomHeader( sprintf( "Content-Type: %s;\n\t boundary=\"%s\"", $content_type, $boundary ) );
	}
	elseif ( is_array($message) ) {
		foreach ($message as $type => $bodies) {
			foreach ((array) $bodies as $body) {
				if ($type === 'text/html') {
					$phpmailer->Body = $body;
				}
				elseif ($type === 'text/plain') {
					$phpmailer->AltBody = $body;
				}
				else {
					$phpmailer->AddAttachment($body, '', 'base64', $type);
				}
			}
		}
	}

	// Add any CC and BCC recipients
	if ( !empty( $cc ) ) {
		foreach ( (array) $cc as $recipient ) {
			try {
				// Break $recipient into name and address parts if in the format "Foo <bar@baz.com>"
				$recipient_name = '';
				if( preg_match( '/(.+)\s?<(.+)>/', $recipient, $matches ) ) {
					if ( count( $matches ) == 3 ) {
						$recipient_name = $matches[1];
						$recipient = $matches[2];
					}
				}
				$phpmailer->AddCc( trim($recipient), $recipient_name );
			} catch ( phpmailerException $e ) {
				continue;
			}
		}
	}

	if ( !empty( $bcc ) ) {
		foreach ( (array) $bcc as $recipient) {
			try {
				// Break $recipient into name and address parts if in the format "Foo <bar@baz.com>"
				$recipient_name = '';
				if( preg_match( '/(.+)\s?<(.+)>/', $recipient, $matches ) ) {
					if ( count( $matches ) == 3 ) {
						$recipient_name = $matches[1];
						$recipient = $matches[2];
					}
				}
				$phpmailer->AddBcc( trim($recipient), $recipient_name );
			} catch ( phpmailerException $e ) {
				continue;
			}
		}
	}

	// Set to use PHP's mail()
	$phpmailer->IsMail();

	// Set custom headers
	if ( !empty( $headers ) ) {
		foreach( (array) $headers as $name => $content ) {
			$phpmailer->AddCustomHeader( sprintf( '%1$s: %2$s', $name, $content ) );
		}
	}

	if ( !empty( $attachments ) ) {
		foreach ( $attachments as $attachment ) {
			try {
				$phpmailer->AddAttachment($attachment);
			} catch ( phpmailerException $e ) {
				continue;
			}
		}
	}

	do_action_ref_array( 'phpmailer_init', array( &$phpmailer ) );

	// Send!
	try {
		$phpmailer->Send();
	} catch ( phpmailerException $e ) {
		return false;
	}

	return true;
}
endif;


if ( ! function_exists('wp_notify_postauthor') ) :
/**
 * Notify an author of a comment/trackback/pingback to one of their posts.
 *
 * @since 1.0.0
 *
 * @param int $comment_id Comment ID
 * @param string $comment_type Optional. The comment type either 'comment' (default), 'trackback', or 'pingback'
 * @return bool False if user email does not exist. True on completion.
 */
function wp_notify_postauthor( $comment_id, $comment_type = '' ) {
	$comment = get_comment( $comment_id );
	$post    = get_post( $comment->comment_post_ID );
	$author  = get_userdata( $post->post_author );

	// The comment was left by the author
	if ( $comment->user_id == $post->post_author )
		return false;

	// The author moderated a comment on his own post
	//if ( $post->post_author == get_current_user_id() )
	//	return false;

	// If there's no email to send the comment to
	if ( '' == $author->user_email )
		return false;

	$comment_author_domain = @gethostbyaddr($comment->comment_author_IP);

	// The blogname option is escaped with esc_html on the way into the database in sanitize_option
	// we want to reverse this for the plain text arena of emails.
	$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
	$eemails_args = array();
	if ( empty( $comment_type ) ) $comment_type = 'comment';

	$eemails_args['template_args'] = array(
										   'comment' => $comment,
										   'comment_type' => $comment_type
										   );
	
	if ('comment' == $comment_type) {
		$notify_message  = sprintf( __( 'New comment on your post "%s"' ), $post->post_title ) . "\r\n";
		/* translators: 1: comment author, 2: author IP, 3: author domain */
		$notify_message .= sprintf( __('Author : %1$s (IP: %2$s , %3$s)'), $comment->comment_author, $comment->comment_author_IP, $comment_author_domain ) . "\r\n";
		$notify_message .= sprintf( __('E-mail : %s'), $comment->comment_author_email ) . "\r\n";
		$notify_message .= sprintf( __('URL    : %s'), $comment->comment_author_url ) . "\r\n";
		$notify_message .= sprintf( __('Whois  : http://whois.arin.net/rest/ip/%s'), $comment->comment_author_IP ) . "\r\n";
		$notify_message .= __('Comment: ') . "\r\n" . $comment->comment_content . "\r\n\r\n";
		$notify_message .= __('You can see all comments on this post here: ') . "\r\n";
		$eemails_args['content_title'] = sprintf( __( '"%s" - new comment' ), $post->post_title );
		/* translators: 1: blog name, 2: post title */
		$subject = sprintf( __('[%1$s] Comment: "%2$s"'), $blogname, $post->post_title );
	} elseif ('trackback' == $comment_type) {
		$notify_message  = sprintf( __( 'New trackback on your post "%s"' ), $post->post_title ) . "\r\n";
		/* translators: 1: website name, 2: author IP, 3: author domain */
		$notify_message .= sprintf( __('Website: %1$s (IP: %2$s , %3$s)'), $comment->comment_author, $comment->comment_author_IP, $comment_author_domain ) . "\r\n";
		$notify_message .= sprintf( __('URL    : %s'), $comment->comment_author_url ) . "\r\n";
		$notify_message .= __('Excerpt: ') . "\r\n" . $comment->comment_content . "\r\n\r\n";
		$notify_message .= __('You can see all trackbacks on this post here: ') . "\r\n";
		$eemails_args['content_title'] = sprintf( __( '"%s" - new traceback' ), $post->post_title );
		/* translators: 1: blog name, 2: post title */
		$subject = sprintf( __('[%1$s] Trackback: "%2$s"'), $blogname, $post->post_title );
	} elseif ('pingback' == $comment_type) {
		$notify_message  = sprintf( __( 'New pingback on your post "%s"' ), $post->post_title ) . "\r\n";
		/* translators: 1: comment author, 2: author IP, 3: author domain */
		$notify_message .= sprintf( __('Website: %1$s (IP: %2$s , %3$s)'), $comment->comment_author, $comment->comment_author_IP, $comment_author_domain ) . "\r\n";
		$notify_message .= sprintf( __('URL    : %s'), $comment->comment_author_url ) . "\r\n";
		$notify_message .= __('Excerpt: ') . "\r\n" . sprintf('[...] %s [...]', $comment->comment_content ) . "\r\n\r\n";
		$notify_message .= __('You can see all pingbacks on this post here: ') . "\r\n";
		$eemails_args['content_title'] = sprintf( __( '"%s" - new pingback' ), $post->post_title );
		/* translators: 1: blog name, 2: post title */
		$subject = sprintf( __('[%1$s] Pingback: "%2$s"'), $blogname, $post->post_title );
	}
	$notify_message .= get_permalink($comment->comment_post_ID) . "#comments\r\n\r\n";
	$notify_message .= sprintf( __('Permalink: %s'), get_permalink( $comment->comment_post_ID ) . '#comment-' . $comment_id ) . "\r\n";
	
	$eemails_args['action_links'] = array();
	if ( EMPTY_TRASH_DAYS ) {
		$notify_message .= sprintf( __('Trash it: %s'), admin_url("comment.php?action=trash&c=$comment_id") ) . "\r\n";
		$eemails_args['action_links'][] = array('link'=>admin_url("comment.php?action=trash&c=$comment_id"),'color'=>'c23031','text'=>__('Trash it'));
	}
	else {
		$notify_message .= sprintf( __('Delete it: %s'), admin_url("comment.php?action=delete&c=$comment_id") ) . "\r\n";
		$eemails_args['action_links'][] = array('link'=>admin_url("comment.php?action=delete&c=$comment_id"),'color'=>'c23031','text'=>__('Delete it'));
	}
	$notify_message .= sprintf( __('Spam it: %s'), admin_url("comment.php?action=spam&c=$comment_id") ) . "\r\n";
	$eemails_args['action_links'][] = array('link'=>admin_url("comment.php?action=spam&c=$comment_id"),'color'=>'d2b12e','text'=>__('Mark as spam'));

	$wp_email = 'wordpress@' . preg_replace('#^www\.#', '', strtolower($_SERVER['SERVER_NAME']));

	if ( '' == $comment->comment_author ) {
		$from = "From: \"$blogname\" <$wp_email>";
		if ( '' != $comment->comment_author_email )
			$reply_to = "Reply-To: $comment->comment_author_email";
	} else {
		$from = "From: \"$comment->comment_author\" <$wp_email>";
		if ( '' != $comment->comment_author_email )
			$reply_to = "Reply-To: \"$comment->comment_author_email\" <$comment->comment_author_email>";
	}

	$message_headers = "$from\n"
		. "Content-Type: text/plain; charset=\"" . get_option('blog_charset') . "\"\n";

	if ( isset($reply_to) )
		$message_headers .= $reply_to . "\n";

	$notify_message = apply_filters('comment_notification_text', $notify_message, $comment_id);
	$subject = apply_filters('comment_notification_subject', $subject, $comment_id);
	$message_headers = apply_filters('comment_notification_headers', $message_headers, $comment_id);
	$eemails_args['headers'] = $message_headers;
	$eemails_args['template'] = 'email-comment-notify';
	@eemails_wp_mail( $author->user_email, $subject, $notify_message, $eemails_args );

	return true;
}
endif;

if ( !function_exists('wp_notify_moderator') ) :
/**
 * Notifies the moderator of the blog about a new comment that is awaiting approval.
 *
 * @since 1.0
 * @uses $wpdb
 *
 * @param int $comment_id Comment ID
 * @return bool Always returns true
 */
function wp_notify_moderator($comment_id) {
	global $wpdb;

	if ( 0 == get_option( 'moderation_notify' ) )
		return true;
		
	$comment = get_comment($comment_id);
	$post = get_post($comment->comment_post_ID);
	$user = get_userdata( $post->post_author );
	// Send to the administation and to the post author if the author can modify the comment.
	$email_to = array( get_option('admin_email') );
	if ( user_can($user->ID, 'edit_comment', $comment_id) && !empty($user->user_email) && ( get_option('admin_email') != $user->user_email) )
		$email_to[] = $user->user_email;

	$comment_author_domain = @gethostbyaddr($comment->comment_author_IP);
	$comments_waiting = $wpdb->get_var("SELECT count(comment_ID) FROM $wpdb->comments WHERE comment_approved = '0'");

	// The blogname option is escaped with esc_html on the way into the database in sanitize_option
	// we want to reverse this for the plain text arena of emails.
	$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
	
	$eemails_args = array();

	$comment_type = $comment->comment_type;
	if ( empty( $comment_type ) ) $comment_type = 'comment';

	$eemails_args['template_args'] = array(
										   'comment' => $comment,
										   'comment_type' => $comment_type
										   );
	switch ($comment->comment_type)
	{
		case 'trackback':
			$notify_message  = sprintf( __('A new trackback on the post "%s" is waiting for your approval'), $post->post_title ) . "\r\n";
			$notify_message .= get_permalink($comment->comment_post_ID) . "\r\n\r\n";
			$notify_message .= sprintf( __('Website : %1$s (IP: %2$s , %3$s)'), $comment->comment_author, $comment->comment_author_IP, $comment_author_domain ) . "\r\n";
			$notify_message .= sprintf( __('URL    : %s'), $comment->comment_author_url ) . "\r\n";
			$notify_message .= __('Trackback excerpt: ') . "\r\n" . $comment->comment_content . "\r\n\r\n";
			$eemails_args['content_title'] = sprintf( __( '"%s" - new traceback awaiting approval' ), $post->post_title );
			break;
		case 'pingback':
			$notify_message  = sprintf( __('A new pingback on the post "%s" is waiting for your approval'), $post->post_title ) . "\r\n";
			$notify_message .= get_permalink($comment->comment_post_ID) . "\r\n\r\n";
			$notify_message .= sprintf( __('Website : %1$s (IP: %2$s , %3$s)'), $comment->comment_author, $comment->comment_author_IP, $comment_author_domain ) . "\r\n";
			$notify_message .= sprintf( __('URL    : %s'), $comment->comment_author_url ) . "\r\n";
			$notify_message .= __('Pingback excerpt: ') . "\r\n" . $comment->comment_content . "\r\n\r\n";
			$eemails_args['content_title'] = sprintf( __( '"%s" - new pingback awaiting approval' ), $post->post_title );
			break;
		default: //Comments
			$notify_message  = sprintf( __('A new comment on the post "%s" is waiting for your approval'), $post->post_title ) . "\r\n";
			$notify_message .= get_permalink($comment->comment_post_ID) . "\r\n\r\n";
			$notify_message .= sprintf( __('Author : %1$s (IP: %2$s , %3$s)'), $comment->comment_author, $comment->comment_author_IP, $comment_author_domain ) . "\r\n";
			$notify_message .= sprintf( __('E-mail : %s'), $comment->comment_author_email ) . "\r\n";
			$notify_message .= sprintf( __('URL    : %s'), $comment->comment_author_url ) . "\r\n";
			$notify_message .= sprintf( __('Whois  : http://whois.arin.net/rest/ip/%s'), $comment->comment_author_IP ) . "\r\n";
			$notify_message .= __('Comment: ') . "\r\n" . $comment->comment_content . "\r\n\r\n";
			$eemails_args['content_title'] = sprintf( __( '"%s" - new comment awaiting approval' ), $post->post_title );
			break;
	}

	$eemails_args['action_links'] = array(array('link'=>admin_url("comment.php?action=approve&c=$comment_id"),'color'=>'3ca757','text'=>__('Approve it')));
																																		   
	$notify_message .= sprintf( __('Approve it: %s'),  admin_url("comment.php?action=approve&c=$comment_id") ) . "\r\n";
	if ( EMPTY_TRASH_DAYS ) {
		$notify_message .= sprintf( __('Trash it: %s'), admin_url("comment.php?action=trash&c=$comment_id") ) . "\r\n";
		$eemails_args['action_links'][] = array('link'=>admin_url("comment.php?action=trash&c=$comment_id"),'color'=>'c23031','text'=>__('Trash it'));
	}
	else {
		$notify_message .= sprintf( __('Delete it: %s'), admin_url("comment.php?action=delete&c=$comment_id") ) . "\r\n";
		$eemails_args['action_links'][] = array('link'=>admin_url("comment.php?action=delete&c=$comment_id"),'color'=>'c23031','text'=>__('Delete it'));
	}
		
	$notify_message .= sprintf( __('Spam it: %s'), admin_url("comment.php?action=spam&c=$comment_id") ) . "\r\n";
	$eemails_args['action_links'][] = array('link'=>admin_url("comment.php?action=spam&c=$comment_id"),'color'=>'d2b12e','text'=>__('Mark as spam'));
	
	$eemails_args['template_args']['moderation_message'] = sprintf( _n('Currently %s comment is waiting for approval. ',
 		'Currently %s comments are waiting for approval.', $comments_waiting), number_format_i18n($comments_waiting) ) . ' <a href="' . admin_url("edit-comments.php?comment_status=moderated") . '" style="color:#21759b;text-decoration:none;">Please visit the moderation panel.</a>';
	$notify_message .= sprintf( _n('Currently %s comment is waiting for approval. Please visit the moderation panel:',
 		'Currently %s comments are waiting for approval. Please visit the moderation panel:', $comments_waiting), number_format_i18n($comments_waiting) ) . "\r\n";
	$notify_message .= admin_url("edit-comments.php?comment_status=moderated") . "\r\n";

	$subject = sprintf( __('[%1$s] Please moderate: "%2$s"'), $blogname, $post->post_title );
	$message_headers = '';

	$notify_message = apply_filters('comment_moderation_text', $notify_message, $comment_id);
	$subject = apply_filters('comment_moderation_subject', $subject, $comment_id);
	$message_headers = apply_filters('comment_moderation_headers', $message_headers);
	
	$eemails_args['headers'] = $message_headers;
	$eemails_args['template'] = 'email-comment-notify';
	foreach ( $email_to as $email )
		@eemails_wp_mail($email, $subject, $notify_message, $eemails_args);

	return true;
}
endif;

if ( !function_exists('wp_password_change_notification') ) :
/**
 * Notify the blog admin of a user changing password, normally via email.
 *
 * @since 2.7
 *
 * @param object $user User Object
 */
function wp_password_change_notification(&$user) {
	// send a copy of password change notification to the admin
	// but check to see if it's the admin whose password we're changing, and skip this
	if ( $user->user_email != get_option('admin_email') ) {
		$eemails_args = array();
		$message = sprintf(__('Password Lost and Changed for user: %s'), $user->user_login) . "\r\n";
		// The blogname option is escaped with esc_html on the way into the database in sanitize_option
		// we want to reverse this for the plain text arena of emails.
		$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
		$eemail_args['template'] = 'email-passwordchange-admin';
		$eemail_args['template_args'] = array('user' => $user);
		$eemails_args['action_links'] = array(
											  array('link'=>admin_url("user-edit.php?user_id=".$user->ID),'color'=>'d2b12e','text'=>__('Edit user')),
											  array('link'=>admin_url("users.php"),'color'=>'3ca757','text'=>__('See all users'))
											  );
		eemails_wp_mail(get_option('admin_email'), sprintf(__('[%s] Password Lost/Changed'), $blogname), $message, $eemail_args);
	}
}
endif;

if ( !function_exists('wp_new_user_notification') ) :
/**
 * Notify the blog admin of a new user, normally via email.
 *
 * @since 2.0
 *
 * @param int $user_id User ID
 * @param string $plaintext_pass Optional. The user's plaintext password
 */
function wp_new_user_notification($user_id, $plaintext_pass = '') {
	$user = new WP_User($user_id);

	$user_login = stripslashes($user->user_login);
	$user_email = stripslashes($user->user_email);

	// The blogname option is escaped with esc_html on the way into the database in sanitize_option
	// we want to reverse this for the plain text arena of emails.
	$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

	$message  = sprintf(__('New user registration on your site %s:'), $blogname) . "\r\n\r\n";
	$message .= sprintf(__('Username: %s'), $user_login) . "\r\n\r\n";
	$message .= sprintf(__('E-mail: %s'), $user_email) . "\r\n";

	$eemail_args['template'] = 'email-newuser-admin';
	$eemail_args['template_args'] = array('user' => $user);
	$eemail_args['action_links'] = array(
										  array('link'=>admin_url("user-edit.php?user_id=".$user->ID),'color'=>'d2b12e','text'=>__('Edit user')),
										  array('link'=>admin_url("users.php"),'color'=>'3ca757','text'=>__('See all users'))
										);
	@eemails_wp_mail(get_option('admin_email'), sprintf(__('[%s] New User Registration'), $blogname), $message, $eemail_args);

	if ( empty($plaintext_pass) )
		return;

	$eemail_args['template_args']["user_password"] = $plaintext_pass;
	$eemail_args['action_links'] = array();
	$eemail_args['template'] = 'email-newuser-user';
	$message  = sprintf(__('Username: %s'), $user_login) . "\r\n";
	$message .= sprintf(__('Password: %s'), $plaintext_pass) . "\r\n";
	$message .= wp_login_url() . "\r\n";

	eemails_wp_mail($user_email, sprintf(__('[%s] Your username and password'), $blogname), $message, $eemail_args);

}
endif;

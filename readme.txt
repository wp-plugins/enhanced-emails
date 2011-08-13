=== Enhanced Emails ===
Contributors: wojtek.szkutnik
Tags: emails, html
Requires at least: 2.7
Tested up to: 3.2
Stable tag:  0.2

The enhanced emails plugin allows users to send html-enriched emails and use email themes.

== Description ==

This plugin is in very early development stage. It is meant for testing purposes only and it is not recommended to use it on production sites.

== Installation ==

1. Upload the `enhancedemails` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= Will the plugin break my current site? =

This is highly unlikely to happen. The plugin enhances the way WordPress handles e-mail notifications, but it does it in a unobtrusive way. Basically, it provides a wrapper for wp_mail, which can be used for e-mail notifications. If you did override any pluggable email notification functions, make sure to use eemails_wp_mail instead of wp_mail in order to make use of the plugin functionality. 

= How can I create my own e-mail theme? -

It's easy - just put your email.php theme file inside your current WP theme directory (if you put it inside wp-content it should work as well, but it's bad practice). 

= How does template hierarchy work for e-mails? =

An example email template name looks like this: email-action1-action2. The plugin tries to locate email-action1-action2.php in your theme folder, and if it fails - it tries to use email.php from your theme. If this fails, too, the plugin will use its default email-action1-action2.php template, or if it doesn't exist - just emails.php. 

== Changelog ==

= 0.1 =
Basic functionalities added.

= 0.2 = 
* Refactored template hierarchy
* Added output sanitization
* Removed output buffering 
* Added a general layout to avoid code duplication
* Theme CSS improvements
* Adjusted wp_mail wrapper to exactly fit the argument structure
* Improved documentation

== Upgrade Notice ==
= 0.2 =
The 0.2 version can be used not only in development, but also on live sites. All major bugs fixed.

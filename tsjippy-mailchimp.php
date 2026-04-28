<?php
namespace TSJIPPY\MAILCHIMP;

/**
 * Plugin Name:  		Tsjippy Mailchimp 
 * Description:  		This plugin adds the possibility to send e-mails via Mailchimp. Post contents can be send as e-mail upon publishing or updating. Any use of the *|ARCHIVE|* placeholder will be replaced with the post url. Create your api key for Mailchimp <a href='https://mailchimp.com/developer/marketing/guides/quick-start/'>here</a>.<br>
 * Version:      		1.0.0
 * Author:       		Ewald Harmsen
 * AuthorURI:			harmseninnigeria.nl
 * Requires at least:	6.3
 * Requires PHP: 		8.3
 * Tested up to: 		6.9
 * Plugin URI:			https://github.com/Tsjippy/mailchimp
 * Tested:				6.9
 * TextDomain:			tsjippy
 * Requires Plugins:	tsjippy-shared-functionality
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 * @author Ewald Harmsen
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pluginData = get_plugin_data(__FILE__, false, false);

// Define constants
define(__NAMESPACE__ .'\PLUGIN', plugin_basename(__FILE__));
define(__NAMESPACE__ .'\PLUGINPATH', __DIR__.'/');
define(__NAMESPACE__ .'\PLUGINVERSION', $pluginData['Version']);
define(__NAMESPACE__ .'\PLUGINSLUG', str_replace('tsjippy-', '', basename(__FILE__, '.php')));
define(__NAMESPACE__ .'\SETTINGS', get_option('tsjippy_mailchimp_settings', []));

// run on activation
register_activation_hook( __FILE__, function(){
	\TSJIPPY\scheduleTask('add_mailchimp_campaigns_action', 'daily');
} );

// run on deactivation
register_deactivation_hook( __FILE__, function(){
} );
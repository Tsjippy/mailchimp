<?php

namespace TSJIPPY\MAILCHIMP;

/**
 * Plugin Name:          Tsjippy Mailchimp
 * Description:          This plugin adds the possibility to send e-mails via Mailchimp. Post contents can be send as e-mail upon publishing or updating. Any use of the *|ARCHIVE|* placeholder will be replaced with the post url. Create your api key for Mailchimp <a href='https://mailchimp.com/developer/marketing/guides/quick-start/'>here</a>.<br>
 * Version:              10.4.0
 * Author:               Ewald Harmsen
 * AuthorURI:            harmseninnigeria.nl
 * Requires at least:    6.3
 * Requires PHP:         8.3
 * Tested up to:         7.0
 * Plugin URI:            https://github.com/Tsjippy/mailchimp
 * Tested:               7.0
 * TextDomain:            tsjippy
 * Requires Plugins:    
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 * @author Ewald Harmsen
 */
if (! defined('ABSPATH')) {
    exit;
}

// Load shared code
if(file_exists(__DIR__  . '/shared-functionality/loader.php')){
    require_once(__DIR__  . '/shared-functionality/loader.php');
}

// Define constants
define(__NAMESPACE__ . '\PLUGIN', plugin_basename(__FILE__));
define(__NAMESPACE__ . '\PLUGINPATH', __DIR__ . '/');
define(__NAMESPACE__ . '\PLUGINVERSION', get_plugin_data(__FILE__, false, false)['Version']);
define(__NAMESPACE__ . '\PLUGINSLUG', str_replace('tsjippy-', '', basename(__FILE__, '.php')));
define(__NAMESPACE__ . '\SETTINGS', get_option('tsjippy_mailchimp_settings', []));

// run right before activation
register_activation_hook(__FILE__, function () {
    if(file_exists(__DIR__  . '/shared-functionality/loader.php')){
        require_once(__DIR__  . '/shared-functionality/loader.php');
    }

    if(function_exists('TSJIPPY\activate')){
        \TSJIPPY\activate();
    }
});
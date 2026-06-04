<?php
namespace TSJIPPY\MAILCHIMP;
use TSJIPPY;
use stdClass;
use WP_Error;

if ( ! defined('ABSPATH')) {
    exit;
}

// Allow rest api urls for non-logged in users
add_filter('tsjippy_allowed_rest_api_urls', __NAMESPACE__ . '\addFormResultUrls');
/**
 * Adds the form result URLs to the list of allowed REST API URLs.
 *
 * @param array $urls The list of allowed REST API URLs.
 * @return array The updated list of allowed REST API URLs.
 */
function addFormResultUrls($urls) {
    $urls[] = RESTAPIPREFIX. '/forms/edit_value';

    return $urls;
}

add_action('rest_api_init', __NAMESPACE__ . '\restApiInit');
function restApiInit() {
    // Mailchimp campaign height
    register_rest_route(
        RESTAPIPREFIX. '/mailchimp',
        '/store_height',
        array(
            'methods'                 => \WP_REST_Server::CREATABLE,
            'callback'                 => function () {
                update_post_meta($_REQUEST['post-id'], 'mailchimp_height', $_REQUEST['height']);

                return true;
            },
            'permission_callback'     => '__return_true',        // Allow non-logged in users to access this endpoint
            'args'                    => array(
                'post-id'        => array(
                    'required'    => true,
                    'validate_callback' => function ($postid) {
                        return is_numeric($postid);
                    }
               ),
                'height'        => array(
                    'required'    => true,
                    'validate_callback' => function ($postid) {
                        return is_numeric($postid);
                    }
               )
           )
       )
   );
}
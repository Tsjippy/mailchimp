<?php
namespace SIM\MAILCHIMP;
use SIM;
use stdClass;
use WP_Error;

// Allow rest api urls for non-logged in users
add_filter('sim_allowed_rest_api_urls', __NAMESPACE__.'\addFormResultUrls');
function addFormResultUrls($urls){
    $urls[] = RESTAPIPREFIX.'/forms/edit_value';

    return $urls;
}

add_action( 'rest_api_init', __NAMESPACE__.'\restApiInit' );
function restApiInit() {
	// Mailchimp campaign height
	register_rest_route(
		RESTAPIPREFIX.'/mailchimp',
		'/store_height',
		array(
			'methods' 				=> \WP_REST_Server::CREATABLE,
			'callback' 				=> function(){
				update_post_meta($_REQUEST['postid'], 'mailchimp_height', $_REQUEST['height']);

				return true;
			},
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'postid'		=> array(
					'required'	=> true,
					'validate_callback' => function($postid){
						return is_numeric($postid);
					}
				),
				'height'		=> array(
					'required'	=> true,
					'validate_callback' => function($postid){
						return is_numeric($postid);
					}
				)
			)
		)
	);
}
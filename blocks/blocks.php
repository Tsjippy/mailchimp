<?php
namespace TSJIPPY\MAILCHIMP;
use TSJIPPY;

// Load the js file to filter all blocks
add_action( 'enqueue_block_editor_assets', __NAMESPACE__.'\blockAssets');
function blockAssets() {
    wp_enqueue_script(
        'tsjippy-mailchimp-block',
        TSJIPPY\pathToUrl(PLUGINPATH.'blocks/mailchimp_options/build/index.js'),
        [ 'wp-blocks', 'wp-dom', 'wp-dom-ready', 'wp-edit-post' ],
        PLUGINVERSION
    );

    $mailchimp  = new Mailchimp();
    $segments   = $mailchimp->getSegments();
    if(!is_array($segments)){
        $segments   = [];
    }

    $segments  = array_map(function($segment){
        return [
            'value' => $segment->id,
            'label' => $segment->name
        ];
    }, $segments);

    wp_localize_script(
        'tsjippy-mailchimp-block',
        'mailchimp',
        $segments
    );
}

// register custom meta tag field
add_action( 'init',  __NAMESPACE__.'\blockInit');
function blockInit(){
	register_post_meta( 
        '', 
        'mailchimp_segment_ids', 
        [
            'show_in_rest' => [
                'schema' => [
                    'type'  => 'array',
                    'items' => [
                        'type' => 'number',
                    ],
                ]
            ],
            'single' 		    => false,
            'type' 			    => 'array',
            'default'	        => [],
            'single'            => true
        ]
    );

	$result=register_post_meta( '', 'mailchimp_email', array(
        'show_in_rest' 	    => true,
        'single' 		    => true,
        'type' 			    => 'string',
		'sanitize_callback' => 'sanitize_text_field'
    ) );

	register_post_meta( '', 'mailchimp_extra_message', array(
        'show_in_rest' 	    => true,
        'single' 		    => true,
        'type' 			    => 'string',
		'sanitize_callback' => 'sanitize_text_field'
    ) );
}
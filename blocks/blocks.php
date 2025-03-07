<?php
namespace SIM\MAILCHIMP;
use SIM;

// Load the js file to filter all blocks
add_action( 'enqueue_block_editor_assets', __NAMESPACE__.'\blockAssets');
function blockAssets() {
    wp_enqueue_script(
        'sim-mailchimp-block',
        SIM\pathToUrl(MODULE_PATH.'blocks/mailchimp_options/build/index.js'),
        [ 'wp-blocks', 'wp-dom', 'wp-dom-ready', 'wp-edit-post' ],
        MODULE_VERSION
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
        'sim-mailchimp-block',
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
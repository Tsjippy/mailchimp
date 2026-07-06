<?php

namespace TSJIPPY\MAILCHIMP;

use TSJIPPY;

if ( ! defined( 'ABSPATH' ) ) exit;

// Load the js file to filter all blocks
add_action('enqueue_block_editor_assets', __NAMESPACE__ . '\blockAssets');
function blockAssets()
{
    wp_enqueue_script(
        'tsjippy-mailchimp-block',
        TSJIPPY\pathToUrl(PLUGINPATH . 'blocks/mailchimp_options/build/index.js'),
        ['wp-blocks', 'wp-dom', 'wp-dom-ready', 'wp-edit-post'],
        PLUGINVERSION
    );
}

// register custom meta tag field
add_action('init',  __NAMESPACE__ . '\blockInit');
function blockInit()
{
    register_post_meta('', "tsjippy_mailchimp_segment_ids", array(
        'show_in_rest'      => true,
        'single'            => false,
        'type'              => 'int',
        'sanitize_callback' => 'absint'
    ));

    register_post_meta('', "tsjippy_mailchimp_email", array(
        'show_in_rest'      => true,
        'single'            => true,
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field'
    ));

    register_post_meta('', "tsjippy_mailchimp_extra_message", array(
        'show_in_rest'      => true,
        'single'            => true,
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field'
    ));
}

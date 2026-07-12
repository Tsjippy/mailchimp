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
    // shows a mailchimp campaign on the page
    register_block_type(
        'tsjippy-mailchimp/show-campaign',
        array(
            'title'           => __( 'Mailchimp Campaign', '%TEXTDOMAIN%' ),
            'attributes'      => array(
                'id'   => array(
                    'label'   => __( 'Campaign Id', '%TEXTDOMAIN%' ),
                    'type'    => 'string',
                    'default' => '',
                ),
                'url'   => array(
                    'label'   => __( 'Campaign Url', '%TEXTDOMAIN%' ),
                    'type'    => 'string',
                    'default' => '',
                ),
            ),
            'render_callback' => __NAMESPACE__.'\mailchimpCode',
            'supports'        => array(
                'autoRegister' => true,
            ),
            'icon'  => 'email'
        )
    );

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

/**
 * Displays a mailchimp campaign. If the height of the campaign is not set, it will be displayed in an iframe. If the height is set, it will be displayed in a div.
 *
 * @param array $atts
 */
function mailchimpCode($atts)
{
    global $post;

    $height = get_post_meta($post->ID, 'tsjippy_mailchimp_height', true);

    $html    = '';
    if ($height == '') {
        $mailchimp = new Mailchimp();

        try{
            $campaignHtml   = $mailchimp->client->campaigns->getContent($atts['id'])->html;
        }catch(\Exception $e){
            if($e->getCode() == 404){
                return "<div class='error'>Campaign not found</div>";
            }
        }

        $dom        = new \DomDocument();
        /** @disregard P1014 */
        $dom->loadHTML($campaignHtml, LIBXML_HTML_NODEFDTD);
        $href       = $dom->getElementById('templateFooter');
        $href->parentNode->removeChild($href);

        $content    = $dom->saveHTML($dom);
        $mergeTags    = ['MC_PREVIEW_TEXT'];

        foreach ($mergeTags as $tag) {
            $content    = str_replace("*|$tag|*", '', $content);
        }

        $html    = "<style>table,td{border: none !important;};#awesomebar{display:none;}</style>";
        $html    .= "<script>
            document.addEventListener('DOMContentLoaded', e => {
                let formData = new FormData();
                formData.append('post-id', $post->ID);
                formData.append('height', document.querySelector('.mailchimp-wrapper').offsetHeight);
                FormSubmit.fetchRestApi('mailchimp/store_height', formData);
            });
        </script>";
        $html    .= "<div class='mailchimp-wrapper'>" . $dom->saveHTML($dom->getElementsByTagName('style')->item(0)) . $content . "</div>";
    } else {
        $url = get_post_meta($post->ID, 'tsjippy_mailchimp_url', true);
        if ($url == '') {
            $mailchimp = new Mailchimp();

            $campaign  = $mailchimp->getCampaign($atts['id']);

            $url       = $campaign->long_archive_url;

            update_post_meta($post->ID, 'tsjippy_mailchimp_url', $url);
        }


        $html    .= "<iframe style='width: 100vw; height: {$height}px; border: none;' src='$url'></iframe>";
    }

    return $html;
}


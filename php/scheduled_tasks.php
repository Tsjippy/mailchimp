<?php

namespace TSJIPPY\MAILCHIMP;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

add_action('init', function () {
    TSJIPPY\scheduleTask('tsjippy-mailchimp-add-campaigns', 'daily', __NAMESPACE__, 'addMailchimpCampaigns');

    // needed for async signal messages
    add_action('tsjippy-mailchimp-schedule-campaign', __NAMESPACE__ . '\asyncMailchimpCampaign');
});

// add mailchimp campains to the website if they have not been created to on the website
function addMailchimpCampaigns()
{
    $mailchimp     = new Mailchimp();

    // get all mailchimp campaigns created yesterday
    $result        = $mailchimp->getCampaigns(gmdate("Y-m-d", strtotime('-1 day')) . 'T00:00:00+00:00');

    $pictures    = SETTINGS['picture-ids'] ?? false;

    $post = array(
        'post_type'        => 'post',
        'post_status'   => "pending",
        'post_author'   => 1
    );

    foreach ($result->campaigns as $campaign) {
        // make sure we do not add the same post twice
        $posts = get_posts(array(
            'numberposts'   => -1,
            'post_status'   => 'any',
            'post_type'     => 'any',
            'meta_query'    => array(
                'relation'      => 'AND',
                array(
                    'key'       => 'tsjippy_mailchimp_campaign_id',
                    'compare'   => 'EXISTS'
                ),
                array(
                    'key'       => 'tsjippy_mailchimp_campaign_id',
                    'value'     => $campaign->id,
                    'compare'   => '='
                ),
            )
        ));

        // do not add mailchimp campaigns created by the website
        if (empty($posts)) {
            $post['post-title']        = $campaign->settings->title;
            if (empty($post['post-title'])) {
                if (!empty($campaign->settings->subject_line)) {
                    $post['post-title']    = $campaign->settings->subject_line;
                } else {
                    continue;
                }
            }
            $post['post_content']   = "[tsjippy_mailchimp id='$campaign->id']";

            $postId                 = wp_insert_post($post, true, false);

            if (is_array($pictures) && isset($pictures['imageId'])) {
                set_post_thumbnail($postId, $pictures['imageId']);
            }

            add_post_meta($postId, "tsjippy_mailchimp_campaign_id", $campaign->id);
        }
    }
}
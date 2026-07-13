<?php

namespace TSJIPPY\MAILCHIMP;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

add_action('tsjippy-frontend-content-post-before-content', __NAMESPACE__ . '\beforeContent');
/**
 * Adds mailchimp url
 * 
 * @param object $object
 */
function beforeContent($object)
{
    // check for mailchimp audience id shortcode
    if (!preg_match('/\[mailchimp id=\'(.*?)\'\]/', $object->postContent, $matches)) {
        return;
    }

    $mailchimp      = new Mailchimp($object->user->ID);
    $campaign       = $mailchimp->getCampaign($matches[1]);

    if (!empty($campaign->long_archive_url)) {
        ?>
        <h4>
            Mailchimp campaign url
        </h4>
        <a href='<?php echo esc_url($campaign->long_archive_url); ?>' target='_blank'>
            Check the online mailchimp campaign
        </a>
        <?php
    }
}

// add the mailchimp fields to the content creation form
add_action('tsjippy-frontend-content-post-after-content', __NAMESPACE__ . '\afterContent', 20);
/**
 * Add the comments section to the frontend post content
 * 
 * @param   object    $object    The FrontEndContent instance
 */
function afterContent($object)
{
    $mailchimpSegmentIds    = $object->getPostMeta('mailchimp_segment_ids', []);
    $mailchimpEmail         = $object->getPostMeta('mailchimp_email', '');
    $mailchimpExtraMessage  = $object->getPostMeta('mailchimp_extra_message', '');
    $Mailchimp              = new Mailchimp($object->user->ID);
    $segments               = $Mailchimp->getSegments();

    if (!$segments) {
        return;
    }

    // If the post is already send to a segment, show that segment
    $sendSegment    = $object->getPostMeta('mailchimp_message_send', false);
    if (is_numeric($sendSegment)) {
        $sendSegment    = [$sendSegment];
    }

    if (is_array($sendSegment)) {
        foreach ($segments as $segment) {
            if ($sendSegment[0] == $segment->id) {
                $sendSegment    = $segment->name;
                break;
            }
        }
    }

    ?>
    <tbody id="mailchimp" class="frontend-form expand-wrapper">
        <tr>
            <td>
                <h4>
                    Mailchimp
                </h4>
            </td>
            <td>
                <button class="button small expand" type='button'>
                    &#9660;
                </button>
            </td>
        </tr>

        <tr>
            <td class="hidden expandable" collspan=2>
                <?php
                if (!empty($sendSegment)) {
                ?>
                    <div class='warning' style='width: fit-content;'>
                        An e-mail has already been send to the <?php echo esc_attr($sendSegment); ?> group.
                    </div>
                <?php
                }
                ?>

                Target segement(s) to send <span class="replace-post-type"><?php echo esc_attr($object->postType); ?></span> contents to on <?php echo $object->update ? 'update' : 'publish'; ?>
                <select name='mailchimp-segment-ids[]' onchange="showMailChimp(this)" multiple='multiple'>
                    <option value="">
                        ---
                    </option>
                    <?php
                    foreach ($segments as $segment) {
                        // Do not send it to the same group twice
                        if ($sendSegment == $segment->id) {
                            continue;
                        }
                    ?>
                        <option value='<?php echo esc_attr($segment->id); ?>' <?php if (in_array($segment->id, $mailchimpSegmentIds)) echo 'selected="selected"'; ?>>
                            <?php echo esc_html($segment->name); ?>
                        </option>
                    <?php
                    }
                    ?>
                </select>

                <div class='mailchimp-wrapper'>
                    <h4>
                        Use this from e-mail address
                    </h4>
                    <input type='text' name='mailchimp-email' list='emails' value='<?php echo esc_attr($mailchimpEmail); ?>'>
                    <datalist id='emails'>
                        <?php
                        $emails = apply_filters('tsjippy-mailchimp-from', []);
                        foreach ($emails as $email => $text) {
                        ?>
                            <option value='<?php echo esc_html($email); ?>'>
                                <?php echo esc_html($text); ?>
                            </option>
                        <?php
                        }
                        ?>
                    </datalist>

                    <h4>
                        Prepend message:
                    </h4>
                    <textarea name='mailchimp-extra-message'>
                        <?php echo esc_html($mailchimpExtraMessage); ?>
                    </textarea>
                </div>
            </td>
        </tr>
    </tbody>
<?php
}

add_action('tsjippy-frontend-content-after-post-save', __NAMESPACE__ . '\afterPostSave', 10, 3);
/**
 * Runs after a post is saved or updated
 * 
 * @param   \WP_Post    $post       The new or updated post
 * @param   object      $object     FrontEndContent Instance
 * @param   array       $request    The sanitized request data
 */
function afterPostSave($post, $object, $request)
{
    if (empty($request['mailchimp-segment-ids'])) {
        return;
    }

    //Mailchimp
    $segmentIds     = $request['mailchimp-segment-ids'];

    if (!is_array($segmentIds)) {
        $segmentIds = explode(",", $segmentIds);
    }

    if (!empty($segmentIds)) {
        $currentSegmentIds   = get_post_meta($post->ID, 'tsjippy_mailchimp_segment_ids');
        $added      = array_diff($segmentIds, $currentSegmentIds);
        $removed    = array_diff($currentSegmentIds, $segmentIds);

        foreach($added as $value){
            add_metadata('post', $post->ID, 'tsjippy_mailchimp_segment_ids', $value);
        }

        foreach($removed as $value){
            delete_metadata('post', $post->ID, 'tsjippy_mailchimp_segment_ids', $value);
        }
        
        update_metadata('post', $post->ID, 'tsjippy_mailchimp_email', $request['mailchimp-email']);
        $extraMessage   = str_replace("\n", '<br>', $request['mailchimp-extra-message']);
        update_metadata('post', $post->ID, 'tsjippy_mailchimp_extra_message', $extraMessage);
    } else {
        delete_metadata('post', $post->ID, 'tsjippy_mailchimp_segment_ids');
        delete_metadata('post', $post->ID, 'tsjippy_mailchimp_email');
        delete_metadata('post', $post->ID, 'tsjippy_mailchimp_extra_message');
    }
}

add_action('wp_after_insert_post', __NAMESPACE__ . '\afterInsertPost', 10, 3);
/**
 * Set the default picture of a post after it is inserted
 *
 * @param    int        $postId        The WP_Post id
 * @param    \WP_Post    $post        The WP_Post object
 */
function afterInsertPost($postId, $post)
{
    if (isset(['publish' => 1, 'inherit' => 1][$post->post_status])) {
        // send asynchronous as sending a campaign is slow
        wp_schedule_single_event(time(), 'tsjippy-mailchimp-schedule-campaign', [$postId]);
    }
}

/**
 * Send the campaign without waiting for the result
 * 
 * @param   int $postId
 */
function asyncMailchimpCampaign($postId)
{
    $segmentIds     = get_post_meta($postId, 'tsjippy_mailchimp_segment_ids');
    $from           = get_post_meta($postId, 'tsjippy_mailchimp_email', true);
    $extraMessage   = get_post_meta($postId, 'tsjippy_mailchimp_extra_message', true);

    if (empty($segmentIds)) {
        return;
    }

    if (empty($from)) {
        TSJIPPY\printArray('No from e-mail address set for Mailchimp campaign', 'error');
        return;
    }

    //Send mailchimp message
    $Mailchimp  = new Mailchimp();
    $segmentIdsSent  = [];
    foreach ($segmentIds as $segmentId) {
        if (!is_numeric($segmentId)) {
            continue;
        }

        $result     = $Mailchimp->sendEmail($postId, intval($segmentId), $from, $extraMessage);

        if ($result == 'succes') {
            $segmentIdsSent[]   = $segmentId;
        }
    }

    // Indicate as send
    if (!empty($segmentIdsSent)) {
        //delete any post metakey
        delete_post_meta($postId, 'tsjippy_mailchimp_segment_ids');
        delete_post_meta($postId, 'tsjippy_mailchimp_email');
        delete_post_meta($postId, 'tsjippy_mailchimp_extra_message');
    }
}

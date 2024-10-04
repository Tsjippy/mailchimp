<?php
namespace SIM\MAILCHIMP;
use SIM;

// add the mailchimp fields to the content creation form
add_action('sim_frontend_post_after_content', function($frontendContend){
    $mailchimpSegmentIds    = $frontendContend->getPostMeta('mailchimp_segment_id');
    $mailchimpEmail		    = $frontendContend->getPostMeta('mailchimp_email');
    $mailchimpExtraMessage  = $frontendContend->getPostMeta('mailchimp_extra_message');
    $Mailchimp              = new Mailchimp($frontendContend->user->ID);
    $segments               = $Mailchimp->getSegments();

    if($segments){
        ?>
        <div id="mailchimp" class="frontendform">
            <h4>Send <span class="replaceposttype"><?php echo $frontendContend->postType;?></span> contents to the following Mailchimp segement(s) on <?php echo $frontendContend->update == 'true' ? 'update' : 'publish';?>:</h4>
            <?php
            $sendSegment    = $frontendContend->getPostMeta('mailchimp_message_send');
            if(is_numeric($sendSegment)){
                $sendSegment    = [$sendSegment];
            }

            if(is_array($sendSegment)){
                foreach($segments as $segment){
                    if($sendSegment == $segment->id){
                        $sendSegment    = $segment->name;
                    }
                }
                ?>
                <div class='warning' style='width: fit-content;'>
                    An e-mail has already been send to the <?php echo $sendSegment;?> group.
                </div>
                <?php
            }
            ?>
            <script>
                function showMailChimp(el){
                    if(el.value == ''){
                        el.closest('div').querySelectorAll('.mailchimp-wrapper').forEach(el => el.classList.add('hidden'));
                    }else{
                        el.closest('div').querySelectorAll('.mailchimp-wrapper').forEach(el => el.classList.remove('hidden'));
                    }
                }
            </script>
            <select name='mailchimp_segment_ids' onchange="showMailChimp(this)" multiple='multiple'>
                <option value="">---</option>
                <?php
                foreach($segments as $segment){
                    // Do not send it to the same group twice
                    if($sendSegment == $segment->id){
                        continue;
                    }elseif(is_array($mailchimpSegmentIds) && in_array($segment->id, $mailchimpSegmentIds)){
                        $selected = 'selected="selected"';
                    }else{
                        $selected = '';
                    }
                    echo "<option value='{$segment->id}' $selected>{$segment->name}</option>";
                }
                ?>
            </select>

            <div class='mailchimp-wrapper hidden'>
                <h4>Use this from e-mail address</h4>
                <input type='text' name='mailchimp_email' list='emails' value='<?php echo $mailchimpEmail;?>'>
                <datalist id='emails'>
                    <?php
                    $emails = apply_filters('sim-mailchimp-from', []);
                    foreach($emails as $email=>$text){
                        echo "<option value='$email'>$text</option>";
                    }
                    ?>
                </datalist>

                <h4>Prepend the e-mail with this message:</h4>
                <textarea name='mailchimp-extra-message'><?php
                    echo $mailchimpExtraMessage;
                ?></textarea>
            </div>
        </div>
        <?php
    }
});

add_action('sim_after_post_save', function($post){
	//Mailchimp
    $segmentIds = explode(",", $_POST['mailchimp_segment_ids']);
	if(is_array($segmentIds) && !empty($segmentIds)){
        $extraMessage   = str_replace("\n", '<br>', sanitize_text_field($_POST['mailchimp-extra-message']));
        update_metadata( 'post', $post->ID,'mailchimp_segment_ids', $segmentIds);
        update_metadata( 'post', $post->ID,'mailchimp_email', $_POST['mailchimp_email']);
        update_metadata( 'post', $post->ID,'mailchimp_extra_message', $extraMessage);
    }else{
        delete_metadata( 'post', $post->ID,'mailchimp_segment_ids');
        delete_metadata( 'post', $post->ID,'mailchimp_email');
        delete_metadata( 'post', $post->ID,'mailchimp_extra_message');
    }
});

add_action( 'wp_after_insert_post', function( $postId, $post ){
    if(in_array($post->post_status, ['publish', 'inherit'])){
        $segmentIds     = (array) get_post_meta($postId, 'mailchimp_segment_ids', true);
        $from           = get_post_meta($postId, 'mailchimp_email', true);
        $extraMessage   = get_post_meta($postId, 'mailchimp_extra_message', true);

        if(empty($segmentIds) || empty($from)){
            return;
        }

        //Send mailchimp message
        $Mailchimp  = new Mailchimp();
        foreach($segmentIds as $segmentId){
            if(!is_numeric($segmentId)){
                continue;
            }

            $result     = $Mailchimp->sendEmail($postId, intval($segmentId), $from, $extraMessage);
        }

        // Indicate as send
        if($result == 'succes'){
            update_metadata( 'post', $postId, 'mailchimp_message_send', $segmentIds);

            //delete any post metakey
            delete_post_meta($postId,'mailchimp_segment_ids');
            delete_post_meta($postId,'mailchimp_email');
            delete_post_meta($postId,'mailchimp_extra_message');
        }
    }
}, 10, 3);
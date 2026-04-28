<?php
namespace TSJIPPY\MAILCHIMP;
use TSJIPPY;

use function TSJIPPY\addElement;
use function TSJIPPY\addRawHtml;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AdminMenu extends \TSJIPPY\ADMIN\SubAdminMenu{

    public function __construct($settings, $name){
        parent::__construct($settings, $name);
    }

    public function settings($parent){
        ob_start();

        $label  = addElement('label', $parent, [], 'Mailchimp API key');
        addElement('input', $label, ['type' => 'text', 'name' => 'apikey', 'id' => 'apikey', 'value' => $this->settings['apikey'] ?? '', 'style' => 'width:100%;']);

        addElement('h4', $parent, [], 'Default picture for imported Mailchimp campaigns');
        $this->pictureSelector('imageId', 'Default Picture', $parent);

        if(!empty($this->settings["apikey"])){
            $mailchimp = new Mailchimp();
            $lists = $mailchimp->getLists();
            if(!is_wp_error($lists)){
                ?>
                <br>
                <label>
                    Mailchimp audience(s) you want new users added to:<br>
                    <?php
                    foreach ($lists as $key => $list){
                        if($this->settings["audienceids"][$key]==$list->id){
                            $checked = 'checked="checked"';
                        }else{
                            $checked = '';
                        }
                        echo '<label>';
                            echo "<input type='checkbox' name='audienceids[$key]' value='$list->id' $checked>";
                            echo $list->name;
                        echo '</label><br>';
                    }
                    ?>
                </label>
                <br>
                <label>
                    Mailchimp TAGs you want to add to new users<br>
                    <input type="text" name="user-tags" value="<?php echo $this->settings["user-tags"]; ?>">
                </label>
                <br>
                <br>
                <?php
                do_action('tsjippy-mailchimp-module-extra-tags', $this->settings);

                ?>
                <div style='margin-bottom:-30px;'>
                    Static mailchimp e-mail html.<br>
                    Insert the placeholder '%content%' where you want post content to be inserted.
                </div>
                <?php
                $tinyMceSettings = array(
                    'wpautop' 					=> false,
                    'media_buttons' 			=> false,
                    'forced_root_block' 		=> true,
                    'convert_newlines_to_brs'	=> true,
                    'textarea_name' 			=> "mailchimp_html",
                    'textarea_rows' 			=> 20
                );

                echo wp_editor(
                    $this->settings["mailchimp_html"],
                    "mailchimp_html",
                    $tinyMceSettings
                );
            }
        }

        addRawHtml(ob_get_clean(), $parent);
        
        return true;
    }

    public function emails($parent){
        return false;
    }

    public function data($parent=''){
        if(empty($this->settings["apikey"])){
            return false;
        }

        $mailchimp = new Mailchimp();
        
        $lists 	= $mailchimp->getLists();

        if(is_wp_error($lists)){
            return false;
        }

        $tab	= $_GET['second-tab'];

        ?>
         <style>
            select{
                display: none;
            }
        </style>
        <?php

        ob_start();

        ?>
        <style>
            select{
                display: none;
            }
        </style>
        <div class='tablink-wrapper'>
            <button class="tablink <?php if(empty($tab) || $tab == 'audience'){echo 'active';}?>" id="show-audience" data-target="audience" >Audiences</button>
            <button class="tablink <?php if($tab == 'campaigns'){echo 'active';}?>" id="show-campaigns" data-target="campaigns">Campaigns</button>
        </div>	
        
        <div class='tabcontent <?php if(!empty($tab) && $tab != 'audience'){echo 'hidden';}?>' id='audience'>
            <table class='tsjippy table'>
                <?php
                foreach ($lists as $key=>$list){
                    $allTags	= $mailchimp->getSegments('static');

                    ?>
                    <tr>
                        <th colspan='5'>Audience <?php echo $list->name;?></th>
                    </tr>
                    <tr>
                        <th>Name</th>
                        <th>E-mail address</th>
                        <th>Member Since</th>
                        <th>Open Rate</th>
                        <th>Tags</th>
                    </tr>
                    <?php

                    $members	= $mailchimp->getListMembersInfo($list->id);
                    usort($members, function ($list1, $list2) { 
                        return strtolower($list1->full_name) > strtolower($list2->full_name); 
                    } ); 

                    foreach($members as $member){
                        $memberSince	= date(DATEFORMAT, strtotime($member->timestamp_opt));
                        $memberTags		= [];
                        $memberTagNames	= [];
                        foreach($member->tags as $tag){
                            $memberTags[]		= $tag->id;
                            $memberTagNames[]	= $tag->name;
                        }

                        if(!empty($_POST['member']) && $_POST['member'] == $member->id){
                            // removed
                            $removed	= array_diff($memberTagNames, $_POST['tags']);
                            foreach($removed as $tagname){
                                $mailchimp->setTag($tagname, 'inactive');
                            }

                            // Added
                            $added		= array_diff($_POST['tags'], $memberTagNames);
                            foreach($added as $tagname){
                                $mailchimp->setTag($tagname, 'active');
                            }
                        }

                        $tagSelect			= "<form action='' method='post'>";
                            $tagSelect			.= "<input type=hidden name='email' value='$member->email_address'>";
                            $tagSelect			.= "<input type=hidden name='member' value='$member->id'>";
                            $tagSelect			.= "<select name='tags[]' id='$member->id' multiple onchange='this.closest(`form`).querySelector(`button`).classList.remove(`hidden`)'>";
                                foreach($allTags as $tag){
                                    if(in_array($tag->id, $memberTags)){
                                        $selected	= 'selected';
                                    }else{
                                        $selected	= '';
                                    }
                                    $tagSelect	.= "<option value='$tag->name' $selected>$tag->name</option>";
                                }
                            $tagSelect			.= "</select>";
                            $tagSelect			.= "<button class='hidden'>Submit</button>";
                        $tagSelect			.= "</form>";

                        $openRate	= $member->stats->avg_open_rate * 100 .'%';
                        echo "<tr>";
                            echo "<td>$member->full_name</td>";
                            echo "<td>$member->email_address</td>";
                            echo "<td>$memberSince</td>";
                            echo "<td>$openRate</td>";
                            echo "<td>$tagSelect</td>";
                        echo "</tr>";
                    }
                }
                ?>
            </table>
        </div>
        <?php

        // get all mailchimp campaigns created this year
        $result		= $mailchimp->getCampaigns(date("Y-m-d", strtotime('-1 year')).'T00:00:00+00:00');

        $nonce		= wp_create_nonce('delete-mailchimp-campaign');
        
        ?>
        <div class='tabcontent <?php if($tab != 'campaigns'){echo 'hidden';}?>' id='campaigns'>
            <table class='tsjippy table'>
                <tr>
                    <th>Title</th>
                    <th>Recipients</th>
                    <th>Sent</th>
                    <th>Open Rate</th>
                    <th>Delete</th>
                </tr>
                <?php
                foreach($result->campaigns as $campaign){
                    $title	= $campaign->settings->title;
                    if(empty($title)){
                        if(!empty($campaign->settings->subject_line)){
                            $title	= $campaign->settings->subject_line;
                        }
                    }

                    $title		= "<a href='$campaign->long_archive_url' target='_blank'>$title</a>";

                    $dateSent	= date(DATEFORMAT.' '.TIMEFORMAT, strtotime($campaign->send_time));

                    $openRate	= round($campaign->report_summary->open_rate * 100 , 1).'%';

                    echo "<tr data-campaign-id='$campaign->id'>";
                        echo "<td>$title</td>";
                        echo "<td>{$campaign->recipients->segment_text}</td>";
                        echo "<td>$dateSent</td>";
                        echo "<td>$openRate</td>";
                        ?>
                        <td>
                            <form method='POST'>
                                <input type='hidden' class='no-reset' name='delete-campaign'	value='<?php echo $campaign->id;?>'>
                                <input type='hidden' class='no-reset' name='nonce' value='<?php echo $nonce;?>'>
                                <button type='submit'>Delete</button>
                            </form>
                        </td>
                        <?php
                    echo "</tr>";
                }
            ?>
            </table>
        </div>
        <?php

        addRawHtml(ob_get_clean(), $parent);

        return true;
    }

    public function functions($parent){

        return false;
    }

    /**
     * Function to do extra actions from $_POST data. Overwrite if needed
     */
    public function postActions(){
        if(isset($_POST['delete-campaign']) && wp_verify_nonce($_POST['nonce'], 'delete-mailchimp-campaign')){
            $mailchimp = new Mailchimp();
            
            $response	= $mailchimp->deleteCampaign($_POST['delete-campaign']);

            ob_start();

            if(empty($response)){
                ?>
                <div class='success'>Campaign <?php echo $_POST['delete-campaign'];?> deleted successfully</div>
                <?php
            }else{
                ?>
                <div class='error'>
                    Campaign <?php echo $_POST['delete-campaign'];?> could not be deleted<br>
                    <?php echo $response;?>
                </div>
                <?php
            }

            return ob_get_clean();
        }
    }
}
<?php

namespace TSJIPPY\MAILCHIMP;

use TSJIPPY;

use function TSJIPPY\addElement;
use function TSJIPPY\addRawHtml;

if (! defined('ABSPATH')) {
    exit;
}

class AdminMenu extends \TSJIPPY\ADMIN\SubAdminMenu
{

    /**
     * AdminMenu constructor.
     *
     * @param array $settings The settings for the plugin
     * @param string $name The name of the plugin
     */
    public function __construct($settings, $name)
    {
        parent::__construct($settings, $name);
    }

    public function settings($parent)
    {
        ob_start();

        $label  = addElement('label', $parent, [], 'Mailchimp API key');
        addElement('input', $label, ['type' => 'text', 'name' => 'apikey', 'id' => 'apikey', 'value' => $this->settings['apikey'] ?? '', 'style' => 'width:100%;']);

        addElement('h4', $parent, [], 'Default picture for imported Mailchimp campaigns');
        $this->pictureSelector('imageId', 'Default Picture', $parent);

        if (!empty($this->settings["apikey"])) {
            $mailchimp = new Mailchimp();
            $lists = $mailchimp->getLists();
            if (!is_wp_error($lists)) {
?>
                <br>
                <label>
                    Mailchimp audience(s) you want new users added to:<br>
                    <?php
                    foreach ($lists as $key => $list) {
                        
                        ?>
                        <label>
                            <input type='checkbox' name='audienceids[<?php esc_attr($key);?>]' value='<?php esc_attr($list->id);?>' if ($this->settings["audienceids"][$key] == $list->id) echo 'checked="checked"';>
                            <?php echo esc_attr($list->name);?>
                        </label><br>
                        <?php
                    }
                    ?>
                </label>
                <br>
                <label>
                    Mailchimp TAGs you want to add to new users<br>
                    <input type="text" name="user-tags" value="<?php echo esc_attr($this->settings["user-tags"]); ?>">
                </label>
                <br>
                <br>
                <?php
                do_action('tsjippy-mailchimp-extra-tags', $this->settings);

                ?>
                <div style='margin-bottom:-30px;'>
                    Static mailchimp e-mail html.<br>
                    Insert the placeholder '%content%' where you want post content to be inserted.
                </div>
        <?php
                $tinyMceSettings = array(
                    'wpautop'                     => false,
                    'media_buttons'             => false,
                    'forced_root_block'         => true,
                    'convert_newlines_to_brs'    => true,
                    'textarea_name'             => "mailchimp_html",
                    'textarea_rows'             => 20
                );

                wp_editor(
                    $this->settings["mailchimp_html"],
                    "mailchimp_html",
                    $tinyMceSettings
                );
            }
        }

        addRawHtml(ob_get_clean(), $parent);

        return true;
    }

    public function emails($parent)
    {
        return false;
    }

    public function data($parent = '')
    {
        if (empty($this->settings["apikey"])) {
            return false;
        }

        $mailchimp = new Mailchimp();

        $lists     = $mailchimp->getLists();

        if (is_wp_error($lists)) {
            return false;
        }

        $tab    = TSJIPPY\sanitize($_GET['second-tab']);

        ob_start();

        ?>
        <div class='tablink-wrapper'>
            <button class="tablink <?php if (empty($tab) || $tab == 'audience') echo 'active'; ?>" id="show-audience" data-target="audience">Audiences</button>
            <button class="tablink <?php if ($tab == 'campaigns') echo 'active'; ?>" id="show-campaigns" data-target="campaigns">Campaigns</button>
        </div>

        <div class='tabcontent <?php if (!empty($tab) && $tab != 'audience') echo 'hidden'; ?>' id='audience'>
            <table class='tsjippy table'>
                <?php
                foreach ($lists as $key => $list) {
                    $allTags    = $mailchimp->getSegments('static');

                ?>
                    <tr>
                        <th colspan='5'>Audience <?php echo esc_attr($list->name); ?></th>
                    </tr>
                    <tr>
                        <th>Name</th>
                        <th>E-mail address</th>
                        <th>Member Since</th>
                        <th>Open Rate</th>
                        <th>Tags</th>
                    </tr>
                <?php

                    $members    = $mailchimp->getListMembersInfo($list->id);
                    usort($members, function ($list1, $list2) {
                        return strtolower($list1->full_name) > strtolower($list2->full_name);
                    });

                    foreach ($members as $member) {
                        $memberTags        = [];
                        $memberTagNames    = [];
                        foreach ($member->tags as $tag) {
                            $memberTags[$tag->id] = 1;
                            $memberTagNames[]    = $tag->name;
                        }

                        if (($_POST['member'] ?? '') == $member->id) {
                            // removed
                            $removed    = array_diff($memberTagNames, TSJIPPY\sanitize($_POST['tags']));
                            foreach ($removed as $tagname) {
                                $mailchimp->setTag($tagname, 'inactive');
                            }

                            // Added
                            $added        = array_diff(TSJIPPY\sanitize($_POST['tags']), $memberTagNames);
                            foreach ($added as $tagname) {
                                $mailchimp->setTag($tagname, 'active');
                            }
                        }

                        ?>
                        <tr>
                            <td>
                                <?php echo esc_html($member->full_name);?>
                            </td>
                            <td>
                                <?php echo esc_html($member->email_address);?>
                            </td>
                            <td>
                                <?php echo esc_html(gmdate(TSJIPPY\DATEFORMAT, strtotime($member->timestamp_opt)));?>
                            </td>
                            <td>
                                <?php echo esc_html($member->stats->avg_open_rate * 100);?>%
                            </td>
                            <td>
                                <form action='' method='post'>";
                                    <input type=hidden name='email' value='<?php esc_attr($member->email_address);?>'>
                                    <input type=hidden name='member' value='<?php esc_attr($member->id);?>'>
                                    <select name='tags[]' id='<?php esc_attr($member->id);?>' multiple onchange='this.closest(`form`).querySelector(`button`).classList.remove(`hidden`)'>
                                        <?php
                                        foreach ($allTags as $tag) {
                                            ?>
                                            <option value='<?php esc_attr($tag->name);?>' <?php if (isset($memberTags[$tag->id])) echo  'selected';?>>
                                                <?php esc_html($tag->name);?>
                                            </option>
                                            <?php
                                        }
                                        ?>
                                    </select>
                                    <button class='hidden'>
                                        Submit
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php
                    }
                }
                ?>
            </table>
        </div>
        <?php

        // get all mailchimp campaigns created this year
        $result        = $mailchimp->getCampaigns(gmdate("Y-m-d", strtotime('-1 year')) . 'T00:00:00+00:00');

        $nonce        = wp_create_nonce('delete-mailchimp-campaign');

        ?>
        <div class='tabcontent <?php if ($tab != 'campaigns') echo 'hidden'; ?>' id='campaigns'>
            <table class='tsjippy table'>
                <tr>
                    <th>Title</th>
                    <th>Recipients</th>
                    <th>Sent</th>
                    <th>Open Rate</th>
                    <th>Delete</th>
                </tr>
                <?php
                foreach ($result->campaigns as $campaign) {
                    $title    = $campaign->settings->title;
                    if (empty($title)) {
                        if (!empty($campaign->settings->subject_line)) {
                            $title    = $campaign->settings->subject_line;
                        }
                    }

                    ?>
                    <tr data-campaign-id='<?php esc_attr($campaign->id);?>'>
                        <td>
                            <a href='<?php esc_url($campaign->long_archive_url);?>' target='_blank'>
                                <?php echo esc_html($title);?>
                            </a>
                        </td>
                        <td>
                            <?php esc_html($campaign->recipients->segment_text);?>
                        </td>
                        <td>
                            <?php echo esc_html(gmdate(TSJIPPY\DATEFORMAT . ' ' . TSJIPPY\TIMEFORMAT, strtotime($campaign->send_time)));?>
                        </td>
                        <td>
                            <?php echo esc_html(round($campaign->report_summary->open_rate * 100, 1) );?>%
                        </td>
                        <td>
                            <form method='POST'>
                                <input type='hidden' class='no-reset' name='delete-campaign' value='<?php echo esc_attr($campaign->id); ?>'>
                                <input type='hidden' class='no-reset' name='nonce' value='<?php echo esc_attr($nonce); ?>'>
                                <button type='submit'>Delete</button>
                            </form>
                        </td>
                    </tr>

                <?php
                }
                ?>
            </table>
        </div>
        <?php

        addRawHtml(ob_get_clean(), $parent);

        return true;
    }

    public function functions($parent)
    {

        return false;
    }

    /**
     * Function to do extra actions from $request data. Overwrite if needed
     */
    public function postActions($request)
    {
        if (isset($request['delete-campaign']) && TSJIPPY\verifyNonce('nonce', 'delete-mailchimp-campaign')) {
            $mailchimp = new Mailchimp();

            $response    = $mailchimp->deleteCampaign($request['delete-campaign']);

            ob_start();

            if (empty($response)) {
            ?>
                <div class='success'>Campaign <?php echo esc_attr($request['delete-campaign']); ?> deleted successfully</div>
            <?php
            } else {
            ?>
                <div class='error'>
                    Campaign <?php echo esc_attr($request['delete-campaign']); ?> could not be deleted<br>
                    <?php echo esc_attr($response); ?>
                </div>
            <?php
            }

            return ob_get_clean();
        }
    }
}

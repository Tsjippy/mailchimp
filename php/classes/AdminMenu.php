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
                            <input type='checkbox' name='audienceids[<?php echo esc_attr($key);?>]' value='<?php echo esc_attr($list->id);?>' <?php if ($this->settings["audienceids"][$key] == $list->id) echo 'checked="checked"';?>>
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

        $screen = get_current_screen();
 
        // get out of here if we are not on our settings page
        if(!is_object($screen) || $screen->id != "tsjippy-settings_page_tsjippy-mailchimp"){
            return;
        }

        /**
         * Add the user defined items per page setting before we instantiate the wp list class
         */
        $option = 'per_page';
        $args = array(
            'label' => 'Campaigns',
            'default' => 10,
            'option' => 'campaigns_per_page'
        );
        add_screen_option( $option, $args );

        $table     = new ExpiredCampaignsTable();

        $mailchimp = new Mailchimp();

        $lists     = $mailchimp->getLists();

        if (is_wp_error($lists)) {
            return false;
        }

        $tab    = TSJIPPY\sanitize($_GET['second-tab'] ?? '');

        ob_start();
        ?>
        <div class='tablink-wrapper'>
            <button class="tablink <?php if (empty($tab) || $tab == 'audience') echo 'active'; ?>" id="show-audience" data-target="audience">
                Audiences
            </button>
            <button class="tablink <?php if ($tab == 'campaigns') echo 'active'; ?>" id="show-campaigns" data-target="campaigns">
                Campaigns
            </button>
            <?php
            if(!empty($table->items)){
                ?>
                <button class="tablink <?php if ($tab == 'invalid-campaigns') echo 'active'; ?>" id="show-invalid-campaigns" data-target="invalid-campaigns">
                    Invalid Campaigns
                </button>
                <?php
            }
            ?>
        </div>

        <div class='tabcontent <?php if (!empty($tab) && $tab != 'audience') echo 'hidden'; ?>' id='audience'>
            <?php
            $mailchimp = new Mailchimp();

            $lists     = $mailchimp->getLists();

            if (is_wp_error($lists)) {
                return false;
            }

            foreach($lists as $list){
                $table      = new Audiences($list);
                if(!empty($table->items)){
                    ?>
                    <div class="wrap">
                        <h2>
                            Mailchimp Audience for <?php echo esc_attr($list->name); ?>
                        </h2>
                        <form method="post">
                            <?php
                            // Display table
                            $table->display();
                            ?>
                        </div>
                    </form>
                    <?php
                }
            }
            ?>
        </div>

        <div class='tabcontent <?php if ($tab != 'campaigns') echo 'hidden'; ?>' id='campaigns'>
            <?php
            $table      = new Campaigns();
            if(!empty($table->items)){
                ?>
                <div class="wrap">
                    <h2>
                        Mailchimp Campaigns
                    </h2>
                    <form method="post">
                        <?php
                        // Display table
                        $table->display();
                        ?>
                    </div>
                </form>
                <?php
            }
            ?>
        </div>

        <?php
        if(!empty($table->items)){
            ?>
            <div class='tabcontent <?php if ($tab != 'invalid-campaigns') echo 'hidden'; ?>' id='invalid-campaigns'>
                <h2>
                    Expired Mailchimp campaigns
                </h2>
                <form method="post">
                    <?php
                    // Display table
                    $table->display();
                    ?>
                </form>
            </div>
            <?php
        }

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

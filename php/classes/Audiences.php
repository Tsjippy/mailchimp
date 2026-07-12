<?php

namespace TSJIPPY\MAILCHIMP;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

// Loading WP_List_Table class file
// We need to load it as it's not automatically loaded by WordPress
if (!class_exists('WP_List_Table')) {
      require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

// Extending class
class Audiences extends \WP_List_Table
{
    public object $list;

    /**
     * Constructor
     * 
     * @param   object   $list   Mailchimp list to show members of
     */
    public function __construct($list){
        parent::__construct();

        $this->list = $list;

        $this->prepareItems();
    }

    /**
     * Get table data
     */
    private function getTableData() {
        $mailchimp = new Mailchimp();

        $allTags    = $mailchimp->getSegments('static');

        $this->items  = [];

        $members    = $mailchimp->getListMembersInfo($this->list->id);

        // Sort on Name
        usort($members, function ($list1, $list2) {
            return strcmp(strtolower($list1->full_name), strtolower($list2->full_name));
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

            ob_start();
            ?>
            <form action='' method='post'>
                <input type=hidden name='email' value='<?php echo esc_attr($member->email_address);?>'>
                <input type=hidden name='member' value='<?php echo esc_attr($member->id);?>'>
                <select name='tags[]' id='<?php echo esc_attr($member->id);?>' multiple onchange='this.closest(`form`).querySelector(`button`).classList.remove(`hidden`)'>
                    <?php
                    foreach ($allTags as $tag) {
                        ?>
                        <option value='<?php echo esc_attr($tag->name);?>' <?php if (isset($memberTags[$tag->id])) echo  'selected';?>>
                            <?php echo esc_html($tag->name);?>
                        </option>
                        <?php
                    }
                    ?>
                </select>
                <button class='hidden'>
                    Submit
                </button>
            </form>
            <?php
            $tagForm    = ob_get_clean();

            $this->items[]    = [
                'name'         => $member->full_name,
                'email'        => $member->email_address,
                'member_since' => gmdate(TSJIPPY\DATEFORMAT, strtotime($member->timestamp_opt)),
                'open_rate'    => $member->stats->avg_open_rate * 100,
                'tags'         => trim($tagForm)
            ];
        }
    }

    // Define table columns
    function getColumns()
    {
        $columns = array(
            'cb'           => '<input type="checkbox" />',
            'name'         => __('Name', '%TEXTDOMAIN%'),
            'email'        => __('E-mail address', '%TEXTDOMAIN%'),
            'member_since' => __('Member Since', '%TEXTDOMAIN%'),
            'open_rate'    => __('Open Rate', '%TEXTDOMAIN%'),
            'tags'         => __('Tags', '%TEXTDOMAIN%')
        );

        return $columns;
    }

    // Define sortable column
    protected function getSortableColumns()
    {
        $sortableColumns = array(
            'name'         => array('name', true),
            'email'        => array('status', true),
            'member_since' => array('order', true),
            'open_rate'    => array('status', true),
            'tags'         => array('status', true)
        );
        
        return $sortableColumns;
    }

    // Bind table with columns, data and all
    function prepareItems()
    {
        //data
        $this->getTableData();

        $columns  = $this->getColumns();
        //$hidden = ( is_array(get_user_meta( get_current_user_id(), 'managetoplevel_page_supporthost_list_tablecolumnshidden', true)) ) ? get_user_meta( get_current_user_id(), 'managetoplevel_page_supporthost_list_tablecolumnshidden', true) : array();
        $sortable = $this->getSortableColumns();
        $primary  = 'campaign';
        $this->_column_headers = array($columns, [], $sortable, $primary);

        usort($this->items, array(&$this, 'usortReorder'));

        /* pagination */
        $perPage     = $this->get_items_per_page('elements_per_page', 10);
        $currentPage = $this->get_pagenum();
        $totalItems  = count($this->items);

        $this->items = array_slice($this->items, (($currentPage - 1) * $perPage), $perPage);

        $this->set_pagination_args(array(
            'total_items' => $totalItems, // total number of items
            'per_page'    => $perPage, // items to show on a page
            'total_pages' => ceil( $totalItems / $perPage ) // use ceil to round up
        ));
    }

    // set value for each column
    function column_default($item, $columnName)
    {
        switch ($columnName) {
            case 'id':
            case 'name':
            case 'description':
            case 'status':
            case 'order':
            default:
                return $item[$columnName];
        }
    }

    // Add a checkbox in the first column
    function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="element[]" value="%s" />',
            $item['id']
        );
    }

    // Sorting function
    function usortReorder($a, $b)
    {
        // If no sort, default to user_login
        $orderby = (!empty($_GET['orderby'])) ? $_GET['orderby'] : 'user_login';

        // If no order, default to asc
        $order = (!empty($_GET['order'])) ? $_GET['order'] : 'asc';

        // Determine sort order
        $result = strcmp($a[$orderby], $b[$orderby]);

        // Send final sort direction to usort
        return ($order === 'asc') ? $result : -$result;
    }

    // Adding action links to column
    function columnName($item)
    {
        $actions = array(
            'edit'   => sprintf('<a href="?page=%s&action=%s&element=%s">' . __('Edit', '%TEXTDOMAIN%') . '</a>', $_REQUEST['page'], 'edit', $item['ID']),
            'delete' => sprintf('<a href="?page=%s&action=%s&element=%s">' . __('Delete', '%TEXTDOMAIN%') . '</a>', $_REQUEST['page'], 'delete', $item['ID']),
        );

        return sprintf('%1$s %2$s', $item['name'], $this->row_actions($actions));
    }

    // To show bulk action dropdown
    function get_bulk_actions()
    {
        $actions = array(
            'delete_all' => __('Delete', '%TEXTDOMAIN%'),
            'draft_all'  => __('Move to Draft', '%TEXTDOMAIN%')
        );
        return $actions;
    }

}
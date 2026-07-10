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
class ExpiredCampaignsTable extends \WP_List_Table
{
    public function __construct(){
        parent::__construct();

        $this->prepareItems();
    }

    // Get table data
    private function getTableData() {
        global $wpdb;

        $posts = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM %i where post_content like %s",
            $wpdb->posts,
            '%'.$wpdb->esc_like('tsjippy-mailchimp/show-campaign').'%'
        ));

        $mailchimp  = new Mailchimp();

        $this->items   = [];

        foreach($posts as $post){
            $blocks = parse_blocks($post->post_content);

            foreach ($blocks as $block) {
                if ('tsjippy-mailchimp/show-campaign' === $block['blockName']) {
                    
                    // Do something when a mailchimp block is found
                    $result = $mailchimp->getCampaign($block['attrs']['id']);

                    if(!is_object($result)){
                        $this->items[] = [
                            'campaignId' => $block['attrs']['id'],
                            "post"       => $post
                        ];
                    }
                }
            }
        }
    }

    // Define table columns
    function getColumns()
    {
        $columns = array(
            'cb'       => '<input type="checkbox" />',
            'campaign' => __('Campaign Id', 'tsjippy'),
            'post_id'  => __('Post ID', 'tsjippy'),
            'post_url' => __('Post Url', 'tsjippy')
        );

        return $columns;
    }

    // Define sortable column
    protected function getSortableColumns()
    {
        $sortableColumns = array(
            'campaign'  => array('name', false),
            'post_id' => array('status', false),
            'post_url'   => array('order', true)
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
        
        $this->items = $this->items;
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
            'edit'   => sprintf('<a href="?page=%s&action=%s&element=%s">' . __('Edit', 'tsjippy') . '</a>', $_REQUEST['page'], 'edit', $item['ID']),
            'delete' => sprintf('<a href="?page=%s&action=%s&element=%s">' . __('Delete', 'tsjippy') . '</a>', $_REQUEST['page'], 'delete', $item['ID']),
        );

        return sprintf('%1$s %2$s', $item['name'], $this->row_actions($actions));
    }

    // To show bulk action dropdown
    function get_bulk_actions()
    {
        $actions = array(
            'delete_all' => __('Delete', 'tsjippy'),
            'draft_all'  => __('Move to Draft', 'tsjippy')
        );
        return $actions;
    }

}
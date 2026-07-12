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
class Campaigns extends \WP_List_Table
{
    public array $notSortable;
    /**
     * Constructor
     * 
     */
    public function __construct(){
        parent::__construct();

        $this->notSortable  = ['cb' => 1];

        $this->prepareItems();

        // Save screen option in usermeta
        add_filter('set-screen-option', function ($status, $option, $value) {
            return $value;
        }, 10, 3);

    }

    public function get_bulk_actions() {
        return [
            'delete'    => 'Delete'
        ];
    }

    /**
     * Get table data
     */
    private function getTableData() {
        $mailchimp   = new Mailchimp();

        // get all mailchimp campaigns created this year
        $result      = $mailchimp->getCampaigns(gmdate("Y-m-d", strtotime('-1 year')) . 'T00:00:00+00:00');

        foreach ($result->campaigns as $campaign) {
            $title    = $campaign->settings->title;
            if (empty($title)) {
                if (!empty($campaign->settings->subject_line)) {
                    $title    = $campaign->settings->subject_line;
                }
            }

            $this->items[]    = [
                'id'         => $campaign->id,
                'title'      => "<a href='$campaign->long_archive_url' target='_blank'> $title</a>",
                'recipients' => $campaign->recipients->segment_text,
                'sent'       => gmdate(TSJIPPY\DATEFORMAT . ' ' . TSJIPPY\TIMEFORMAT, strtotime($campaign->send_time)),
                'open_rate'  => round($campaign->report_summary->open_rate * 100, 1) .'%'
            ];
        }
    }

    // Define table columns
    private function getColumns()
    {
        $columns = array(
            'cb'         => '<input type="checkbox" />',
            'title'      => __('Title', '%TEXTDOMAIN%'),
            'recipients' => __('Recipients', '%TEXTDOMAIN%'),
            'sent'       => __('Sent', '%TEXTDOMAIN%'),
            'open_rate'  => __('Open Rate', '%TEXTDOMAIN%')
        );

        return $columns;
    }

    // Define sortable column
    protected function getSortableColumns()
    {
        $sortableColumns    = [];

        foreach($this->getColumns() as $key => $description){
            if(isset($this->notSortable[$key] )){
                continue;
            }

            $sortableColumns[$key]  = [$key, false]; // false for unsorted
        }
        
        return $sortableColumns;
    }

    // Bind table with columns, data and all
    public function prepareItems()
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
        $perPage     = $this->get_items_per_page('campaigns_per_page', 10);
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
    public function column_default($item, $columnName)
    {
        return $item[$columnName] ?? '';
    }

    // Add a checkbox in the first column
    public function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="element[]" value="%s" />',
            $item['id']
        );
    }

    // Sorting function
    public function usortReorder($a, $b)
    {
        // If no sort, default to title
        $orderby = (!empty($_GET['orderby'])) ? $_GET['orderby'] : 'title';

        // If no order, default to asc
        $order = (!empty($_GET['order'])) ? $_GET['order'] : 'asc';

        // Determine sort order
        $result = strcmp($a[$orderby], $b[$orderby]);

        // Send final sort direction to usort
        return ($order === 'asc') ? $result : -$result;
    }

    /**
     * Adding action links to title column
     * 
     * @param   array   $item   The cell data
     */
    public function column_title($item)
    {
        $actions = array(
            'delete' => sprintf('<a href="?page=%s&action=%s&element=%s">' . __('Delete', '%TEXTDOMAIN%') . '</a>', $_REQUEST['page'], 'delete', $item['id']),
        );

        return sprintf('%1$s %2$s', $item['title'], $this->row_actions($actions));
    }

}
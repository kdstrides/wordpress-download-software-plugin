<?php

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class Donwload_Software_List_Table extends WP_List_Table
{
    function __construct()
    {
        global $status, $page;

        parent::__construct(array(
            'singular' => 'serial number',
            'plural' => 'serial numbers',
        ));
    }

    function column_default($item, $column_name)
    {
        return $item[$column_name];
    }

    function column_name($item)
    {
        $actions = array(
            'edit' => sprintf('<a href="?page=download_software_form&id=%s">%s</a>', $item['id'], __('Edit', 'download_software')),
            'delete' => sprintf('<a href="?page=%s&action=delete&id=%s">%s</a>', $_REQUEST['page'], $item['id'], __('Delete', 'download_software')),
        );

        return sprintf('%s %s %s %s',
            $item['serial_number'],
            $item['download_count'],
            $item['location'],
            $this->row_actions($actions)
        );
    }

    function column_action($item) {
        return sprintf('<a href="?page=download_software_form&id=%s">%s</a>', $item['id'], __('Edit', 'download_software')) . " | " . sprintf('<a href="?page=%s&action=delete&id=%s">%s</a>', $_REQUEST['page'], $item['id'], __('Delete', 'download_software'));
    }

    function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="id[]" value="%s" />',
            $item['id']
        );
    }

    function get_columns()
    {
        $columns = array(
            'cb' => '<input type="checkbox" />',
            'serial_number' => __('Serial number'),
            'download_count' => __('Download count'),
            'location' => __('Location'),
            'action' => __('Action'),
        );
        return $columns;
    }

    function get_sortable_columns()
    {
        $sortable_columns = array(
            'serial_number' => array('serial_number', true),
            'download_count' => array('download_count', false),
        );
        return $sortable_columns;
    }

    function get_bulk_actions()
    {
        $actions = array(
            'delete' => 'Delete'
        );
        return $actions;
    }

    function process_bulk_action()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'serial_number';

        if ('delete' === $this->current_action()) {
            $ids = isset($_REQUEST['id']) ? $_REQUEST['id'] : array();
            if (is_array($ids)) $ids = implode(',', $ids);

            if (!empty($ids)) {
                $wpdb->query("DELETE FROM $table_name WHERE id IN($ids)");
            }
        }
    }

    function prepare_items()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'serial_number';

        $per_page = 15;

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array($columns, $hidden, $sortable);

        $this->process_bulk_action();

        $total_items = $wpdb->get_var("SELECT COUNT(id) FROM $table_name");

        $paged = isset($_REQUEST['paged']) ? ($per_page * max(0, intval($_REQUEST['paged']) - 1)) : 0;
        $orderby = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array_keys($this->get_sortable_columns()))) ? $_REQUEST['orderby'] : 'serial_number';
        $order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc'))) ? $_REQUEST['order'] : 'asc';

        $this->items = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $paged), ARRAY_A);

        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page' => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ));
    }
}

function download_software_admin_menu()
{
    add_menu_page(
        'List serial numbers',
        'Download Software',
        'manage_options',
        'admin-download-software-menu',
        'download_software_page_handler',
        '',
        20
    );

    add_submenu_page(
        'admin-download-software-menu',
        'Downloads log',
        'List downloads log',
        'manage_options',
        'admin-download-software-serial',
        'admin_download_software_list_dowloads_log'
    );

    add_submenu_page(
        'admin-download-software-menu',
        __('Add new serial number'),
        __('Add new serial number'),
        'manage_options',
        'download_software_form',
        'download_software_download_software_form_page_handler'
    );
}

add_action('admin_menu', 'download_software_admin_menu');

function download_software_page_handler()
{
    global $wpdb;
    if (isset($_POST['upload_submit']))
    {
        $table_name = $wpdb->prefix . 'serial_number';
        $fext = $_FILES['file']['name'];
        $ext = pathinfo($fext, PATHINFO_EXTENSION);

        if (!empty($_FILES['file']['name'])) {
            if ($ext == 'csv') {
                $csvFile = fopen($_FILES['file']['tmp_name'], 'r');
                fgetcsv($csvFile);
                while (($line = fgetcsv($csvFile)) !== false) {
                    $serialNumber = $line[0];
                    $location = $line[1];
                    $exists = $wpdb->get_results("SELECT * FROM $table_name WHERE serial_number = '$serialNumber'");
                    if (!$exists) {
                        $wpdb->insert($table_name, array(
                            'serial_number' => $serialNumber,
                            'location' => $location
                        ));
                    }
                }
                fclose($csvFile);
                echo '<div class="updated"><p>Data Uploaded Successfully</p></div>';
            } else {
                echo '<div class="error"><p>Oops! Something Went Wrong!</h1>Try again with valid CSV file.</p></div>';
            }
        } else {
            echo '<div class="error"><p>Oops! Something Went Wrong!</h1>Try again with valid CSV file.</p></div>';
        }
    }

    $table = new Donwload_Software_List_Table();
    $table->prepare_items();

    $message = '';
    if ('delete' === $table->current_action()) {
        $message = '<div class="updated below-h2" id="message"><p>' . sprintf(__('Items deleted: %d', 'download_software'), count($_REQUEST['id'])) . '</p></div>';
    }
    ?>
<div class="wrap">
    <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
    <h2>
        <?php _e('List downloads', 'download_software')?>
        <a class="add-new-h2" href="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page=download_software_form');?>"><?php _e('Add new', 'download_software')?></a>
    </h2>
    <?php echo $message; ?>
    <div class="card" style="width:100%;">
        <form method="post" enctype="multipart/form-data" action="">
            File:<input name="file" type="file" id="csvfile" accept=".csv" />
            <input type="submit" name="upload_submit" value="Upload File">
        </form>
    </div>
    <form id="persons-table" method="GET">
        <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>"/>
        <?php $table->display() ?>
    </form>
</div>
<?php
}

function download_software_download_software_form_page_handler()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'serial_number';

    $message = '';
    $notice = '';

    $default = array(
        'id' => 0,
        'serial_number' => '',
        'download_count' => '',
        'location' => '',
    );

    if (wp_verify_nonce($_REQUEST['nonce'], basename(__FILE__))) {
        $item = shortcode_atts($default, $_REQUEST);
        $item_valid = download_software_validate($item);
        if ($item_valid === true) {
            if ($item['id'] == 0) {
                $serialNumber = $_REQUEST['serial_number'];

                $exists = $wpdb->get_results("SELECT * FROM $table_name WHERE serial_number = '$serialNumber'");

                if(!empty($exists)) {
                    $notice = __('Serial number already exists.');
                } else {
                    $result = $wpdb->insert($table_name, $item);
                    $item['id'] = $wpdb->insert_id;
                    if ($result) {
                        $message = __('Item was successfully saved', 'download_software');
                    } else {
                        $notice = __('There was an error while saving item', 'download_software');
                    }
                }
            } else {
                $updateID = $item['id'];
                $serialNumber = $item['serial_number'];

                $exists = $wpdb->get_results("SELECT * FROM $table_name WHERE serial_number = '$serialNumber' AND id != '$updateID'");

                if (!empty($exists)) {
                    $notice = __('Serial number already exists.');
                } else {
                    $wpdb->query("UPDATE $table_name SET serial_number = '$serialNumber' WHERE id = '$updateID'");
                    $message = __('Item was successfully updated', 'download_software');
                }
            }
        } else {
            $notice = $item_valid;
        }
    }
    else {
        $item = $default;
        if (isset($_REQUEST['id'])) {
            $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $_REQUEST['id']), ARRAY_A);
            if (!$item) {
                $item = $default;
                $notice = __('Item not found', 'download_software');
            }
        }
    }

    add_meta_box('download_software_form_meta_box', 'Serial number data', 'download_software_download_software_form_meta_box_handler', 'serial_numbers_list', 'normal', 'default');

    ?>
<div class="wrap">
    <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
    <h2>
        <?php
            if($_REQUEST['id']) {
                _e('Edit', 'download_software');
            } else {
                _e('New', 'download_software');
            }
            
        ?>
        <a class="add-new-h2" href="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page=admin-download-software-menu');?>"><?php _e('back to list', 'download_software')?></a>
    </h2>

    <?php if (!empty($notice)): ?>
        <div id="notice" class="error"><p><?php echo $notice ?></p></div>
    <?php endif;?>
    <?php if (!empty($message)): ?>
        <div id="message" class="updated"><p><?php echo $message ?></p></div>
    <?php endif;?>

    <form id="form" method="POST">
        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce(basename(__FILE__))?>"/>
        <input type="hidden" name="id" value="<?php echo $item['id'] ?>"/>
        <div class="metabox-holder" id="poststuff">
            <div id="post-body">
                <div id="post-body-content">
                    <?php do_meta_boxes('serial_numbers_list', 'normal', $item); ?>
                    <input type="submit" value="<?php _e('Save', 'download_software')?>" id="submit" class="button-primary" name="submit">
                </div>
            </div>
        </div>
    </form>
</div>
<?php
}

function download_software_download_software_form_meta_box_handler($item)
{
    ?>

<table cellspacing="2" cellpadding="5" style="width: 100%;" class="form-table">
    <tbody>
        <tr class="form-field">
            <th valign="top" scope="row">
                <label for="name"><?php _e('Serial number', 'download_software')?></label>
            </th>
            <td>
                <input id="serial_number" name="serial_number" type="text" style="width: 95%" value="<?php echo esc_attr($item['serial_number'])?>" size="50" class="code" placeholder="<?php _e('Serial number', 'download_software')?>" required />
            </td>
        </tr>
        <tr class="form-field">
            <th valign="top" scope="row">
                <label for="email"><?php _e('Location', 'download_software')?></label>
            </th>
            <td>
                <input id="location" name="location" type="input" style="width: 95%" value="<?php echo esc_attr($item['location'])?>" size="50" class="code" placeholder="<?php _e('Location', 'download_software')?>" required />
            </td>
        </tr>
    </tbody>
</table>
<?php
}

function download_software_validate($item)
{
    $messages = array();

    if (empty($item['serial_number'])) $messages[] = __('Serial number is required', 'download_software');
    if (empty($item['location'])) $messages[] = __('Location is required', 'download_software');
    
    if (empty($messages)) return true;
    return implode('<br />', $messages);
}

function admin_download_software_list_dowloads_log() {
    global $title, $wpdb, $table_prefix;
    $wp_serial_log = $table_prefix . "serial_number_log";
    $page_num = isset($_GET['pagenum']) ? $_GET['pagenum'] : 1;
    $limit = 10;
    $offset = ($page_num - 1) * $limit;
    $total = $wpdb->get_var("SELECT COUNT(`id`) FROM $wp_serial_log");
    $num_of_pages = ceil($total / $limit);
    $lists = $wpdb->get_results("SELECT * FROM $wp_serial_log ORDER BY id DESC LIMIT $offset,$limit");
    ?>
    <style>
        .pagination a {
            padding: 7px 12px;
            border: 1px solid #cecece;
            background-color: white;
            border-radius: 2px;
            text-align: center;
            text-decoration: none;
        }
    </style>
    <h2><?php echo $title; ?></h2>
    <table class="widefat fixed" cellspacing="0">
        <thead>
            <tr>
                <th class="manage-column column-columnname" scope="col">Serial Number</th>
                <th class="manage-column column-columnname" scope="col">Email address</th>
                <th class="manage-column column-columnname" scope="col">IP</th>
            </tr>
        </thead>
        <tfoot>
            <tr>
                <th class="manage-column column-columnname" scope="col">Serial Number</th>
                <th class="manage-column column-columnname" scope="col">Email address</th>
                <th class="manage-column column-columnname" scope="col">IP</th>
            </tr>
        </tfoot>
        <tbody>
            <?php
            if (!$lists) {
                echo '<tr><td colspan="4">No serial number found.</td></tr>';
            } else {
                foreach ($lists as $key => $value) {
                    $email_address = $value->email_address ? $value->email_address : 'N/A';
                    $IP = $value->IP ? $value->IP : 'N/A';
                    echo '<tr><td class="column-columnname">' . $value->serial_number . '</td><td class="column-columnname" align="left">' . $email_address . '</td><td class="column-columnname" align="left">' . $IP . '</td></tr>';
                }
            }
            ?>
        </tbody>
    </table>
    <?php
    $page_links = paginate_links(array(
        'base' => add_query_arg('pagenum', '%#%'),
        'format' => '',
        'prev_text' => __('«', 'text-domain'),
        'next_text' => __('»', 'text-domain'),
        'total' => $num_of_pages,
        'current' => $page_num,
    ));
    if ($page_links) {
        echo '<div class="pagination-wrap"><div class="pagination" style="margin: 1em 0;">' . $page_links . '</div></div>';
    }
}
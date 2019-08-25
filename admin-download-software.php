<?php

function admin_download_software_menu()
{
    add_menu_page(
        'List serial numbers',
        'Download Software',
        'manage_options',
        'admin-download-software-menu',
        'admin_download_software_list_upload_serial',
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
}

function admin_download_software_list_upload_serial()
{
    global $title, $wpdb, $table_prefix;

    $wp_serial_number = $table_prefix . "serial_number";
    $wp_serial_log = $table_prefix . "serial_number_log";

    if (isset($_POST['upload_submit'])) {
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
                echo '<div> <h1>Data Uploaded Successfully</h1></div>';
            } else {
                echo '<h1>Oops! Something Went Wrong!</h1>Try again with valid CSV file.';
            }
        } else {
            echo '<h1>Oops! Something Went Wrong!</h1>Error processing CSV file.';
        }
    }

    $page_num = isset($_GET['pagenum']) ? $_GET['pagenum'] : 1;
    $limit = 10;
    $offset = ($page_num - 1) * $limit;
    $total = $wpdb->get_var("SELECT COUNT(`id`) FROM $wp_serial_number");
    $num_of_pages = ceil($total / $limit);

    $lists = $wpdb->get_results("SELECT * FROM $wp_serial_number ORDER BY id DESC LIMIT $offset,$limit");
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
    <div class="wrap card">
        <form method="post" enctype="multipart/form-data" action="">
            File:<input name="file" type="file" id="csvfile" accept=".csv" />
            <input type="submit" name="upload_submit" value="Upload File">
        </form>
    </div>
    <br/><br/>

    <table class="widefat fixed" cellspacing="0">
        <thead>
            <tr>
                <th class="manage-column column-columnname" scope="col">Serial Number</th>
                <th class="manage-column column-columnname num" scope="col">Location</th>
                <th class="manage-column column-columnname num" scope="col">Donwload Count</th>
            </tr>
        </thead>
        <tfoot>
            <tr>
                <th class="manage-column column-columnname" scope="col">Serial Number</th>
                <th class="manage-column column-columnname num" scope="col">Location</th>
                <th class="manage-column column-columnname num" scope="col">Donwload Count</th>
            </tr>
        </tfoot>
        <tbody>
            <?php
            if (!$lists) {
                echo '<tr><td colspan="3">No serial number found.</td></tr>';
            } else {
                foreach ($lists as $key => $value) {
                    $IP = $value->IP ? $value->IP : 'N/A';

                    echo '<tr><td class="column-columnname">' . $value->serial_number . '</td><td class="column-columnname" align="center">' . $value->location . '</td><td class="column-columnname" align="center">' . $value->download_count . '</td></tr>';
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

                    echo '<tr><td class="column-columnname">' . $value->serial_number . '</td><td class="column-columnname" align="center">' . $email_address . '</td><td class="column-columnname" align="center">' . $IP . '</td></tr>';
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

function admin_download_software_upload_software() {

}

add_action('admin_menu', 'admin_download_software_menu');
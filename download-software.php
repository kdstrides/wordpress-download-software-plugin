<?php
/*
Plugin Name: Download Software
Plugin URI: http://wordpress.com
Description: Provide form which will validate serial number and show download links to user.
Version: 1.0
Author: Adam Plaga
*/

require_once('admin-download-software.php');
require_once('admin-download-software-upload.php');

function download_software_render_form_shortcode($atts, $content)
{
    $atts = shortcode_atts(array(
        'serial-number-field' => 'Serial number',
        'email-field' => 'Email Address',
        'submit-btn-label' => 'Submit',
    ), $atts);

    ob_start();
    ?>

    <form class="download_software_form" method="post">
        <div class="row">
            <div class="col-sm-6">
                <input type="text" required name="_serial_number" placeholder="<?php echo esc_attr($atts['serial-number-field']); ?>">
            </div>
            <div class="col-sm-6">
                <input type="email" required name="_email" placeholder="<?php echo esc_attr($atts['email-field']); ?>">
            </div>
        </div>
        <input type="submit" name="submit" value="<?php echo esc_attr($atts['submit-btn-label']); ?>"/>
    </form>
    <div id="download-links"></div>
    <?php
return ob_get_clean();
}

function download_software_check_serial()
{
    if (isset($_POST['data'])) {
        global $wpdb;

        $wp_serial_number = $wpdb->prefix . "serial_number";
        $wp_serial_log = $wpdb->prefix . "serial_number_log";
        $data = array();
        wp_parse_str($_POST['data'], $data);

        $serialNo = !empty($data['_serial_number']) ? $data['_serial_number'] : '';
        $email = !empty($data['_email']) ? $data['_email'] : '';

        if($serialNo == "") {
            echo json_encode([
                'error' => true,
                'message' => '<span style="color:red;">Please provide valid serial number.</span>'
            ]);
            exit;
        }

        if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode([
                'error' => true,
                'message' => '<span style="color:red;">Please provide valid email address.</span>'
            ]);
            exit;
        }

        $serialnumber = $wpdb->get_row("SELECT * FROM $wp_serial_number WHERE serial_number = '$serialNo'");

        if(!$serialnumber) {
            echo json_encode([
                'error' => true,
                'message' => '<span style="color:red;">Invalid serial number. Please try again with valid serial number.</span>'
            ]);
            exit;
        }

        $logIP = "";
        if(!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $logIP = $_SERVER['HTTP_CLIENT_IP'];
        } elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $logIP = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $logIP = $_SERVER['REMOTE_ADDR'];
        }

        // $existsLog = $wpdb->get_results("SELECT * FROM $wp_serial_log WHERE email_address = '$email' AND serial_number = '$serialNo' AND IP = '$logIP'");

        // if (!$existsLog) {
        $wpdb->insert($wp_serial_log, array(
            'serial_number' => $serialNo,
            'email_address' => $email,
            'IP' => $logIP
        ));
        $wpdb->query("UPDATE $wp_serial_number SET download_count = download_count + 1 WHERE serial_number = '$serialNo'");
        // }

        $cs_base_dir = WP_PLUGIN_URL . '/' . str_replace(basename(__FILE__), "", plugin_basename(__FILE__));


        $macDLink = $cs_base_dir."download.php?store=mac&serial=".$serialNo;
        $winDLink = $cs_base_dir."download.php?store=win&serial=".$serialNo;

        $downloadURlMac = "<a href='".$macDLink."' target='_blank'>Download for Mac</a>";
        $downloadURlWindow = "<a href='".$winDLink."' target='_blank'>Download for Window</a>";

        echo json_encode([
            'error' => false,
            'message' => '<span style="color:green;">Your download links are ready.</span>'.$downloadURlMac . $downloadURlWindow
        ]);
        exit;
    }
}

function download_software_database_table()
{
    global $table_prefix, $wpdb;
    require_once ABSPATH . '/wp-admin/includes/upgrade.php';

    $serialNumberTable = 'serial_number';
    $serialNumberLogTable = 'serial_number_log';

    $wp_serial_number = $table_prefix . "$serialNumberTable";
    $wp_serial_number_log = $table_prefix . "$serialNumberLogTable";

    $sqlSerialNumber = array();

    if ($wpdb->get_var("show tables like '$wp_serial_number'") != $wp_serial_number) {
        $sqlSerialNumber[0] = "CREATE TABLE `" . $wp_serial_number . "` ( ";
        $sqlSerialNumber[0] .= "  `id`  int(10) UNSIGNED NOT NULL auto_increment, ";
        $sqlSerialNumber[0] .= "  `serial_number`  VARCHAR(128)   NOT NULL, ";
        $sqlSerialNumber[0] .= "  `download_count` int(10) UNSIGNED NOT NULL, ";
        $sqlSerialNumber[0] .= "  PRIMARY KEY `id` (`id`) ";
        $sqlSerialNumber[0] .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ; ";
    }

    if ($wpdb->get_var("show tables like '$wp_serial_number_log'") != $wp_serial_number_log) {
        $sqlSerialNumber[1] = "CREATE TABLE `" . $wp_serial_number_log . "` ( ";
        $sqlSerialNumber[1] .= "  `id`  int(10) UNSIGNED NOT NULL auto_increment, ";
        $sqlSerialNumber[1] .= "  `email_address`  VARCHAR(150)  NOT NULL, ";
        $sqlSerialNumber[1] .= "  `serial_number`  VARCHAR(128)  NOT NULL, ";
        $sqlSerialNumber[1] .= "  `IP` VARCHAR(20) NOT NULL, ";
        $sqlSerialNumber[1] .= "  PRIMARY KEY `id` (`id`) ";
        $sqlSerialNumber[1] .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ; ";
    }
    dbDelta($sqlSerialNumber);
}

register_activation_hook(__FILE__, 'download_software_database_table');

function download_software_enqueue_scripts()
{
    wp_enqueue_script('download-software-js', plugin_dir_url(__FILE__) . 'download-software.js', array('jquery'));
    wp_localize_script('download-software-js', 'download_software_ajax_script', array('ajaxurl' => admin_url('admin-ajax.php')));
}

add_action("wp_enqueue_scripts", "download_software_enqueue_scripts");
add_action('wp_ajax_download_software_check_serial', 'download_software_check_serial');
add_action('wp_ajax_nopriv_download_software_check_serial', 'download_software_check_serial');
add_shortcode('download_software_form', 'download_software_render_form_shortcode');
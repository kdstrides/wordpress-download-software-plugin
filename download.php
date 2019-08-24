<?php
require_once( str_replace('//','/',dirname(__FILE__).'/') .'../../../wp-config.php');

if ($_GET['store'] && in_array($_GET['store'], array('mac','win'))) {
    if ($_GET['serial'] && $_GET['serial'] != "") {
        global $title, $wpdb, $table_prefix;
        $wp_serial_number = $wpdb->prefix . "serial_number";
        $serialnumber = $_GET['serial'];
        $total = $wpdb->get_var("SELECT COUNT(`id`) FROM $wp_serial_number WHERE serial_number = '$serialnumber'");

        $downloadLink = "";

        if($_GET['store'] == "mac") {
            $downloadLink = get_option('mac_download_link');
        } else {
            $downloadLink = get_option('window_download_link');
        }
        if($total) {
            wp_redirect($downloadLink);
        } else {
            echo "<h1>Download link expired.</h1>";
        }
    }
} else {
    echo "<h1>Download link expired.</h1>";
}
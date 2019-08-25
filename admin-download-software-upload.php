<?php

function admin_upload_software_settings_page()
{
    add_settings_section(
        "upload_software",
        "Upload Software",
        null,
        "admin_upload_software"
    );
    add_settings_field(
        "admin_upload_software_mac",
        "Upload Mac File",
        "admin_upload_software_file_mac_display",
        "admin_upload_software",
        "upload_software"
    );
    add_settings_field(
        "admin_upload_software_win",
        "Upload Window File",
        "admin_upload_software_file_win_display",
        "admin_upload_software",
        "upload_software"
    );
    register_setting("admin_upload_software", "admin_upload_software_mac", "handle_file_upload_mac");
    register_setting("admin_upload_software", "admin_upload_software_win", "handle_file_upload_win");
}

function handle_file_upload_mac($option)
{
    if ($_FILES["admin_upload_software_mac"]["tmp_name"] && $_FILES["admin_upload_software_mac"]["tmp_name"] != "") {
        $urls = wp_handle_upload($_FILES["admin_upload_software_mac"], array('test_form' => false));
        $temp = $urls["url"];
        return $temp;
    }
    return $option;
}

function handle_file_upload_win($option)
{
    if ($_FILES["admin_upload_software_win"]["tmp_name"] && $_FILES["admin_upload_software_win"]["tmp_name"] != "") {
        $urls = wp_handle_upload($_FILES["admin_upload_software_win"], array('test_form' => false));
        $temp = $urls["url"];
        return $temp;
    }
    return $option;
}

function admin_upload_software_file_mac_display()
{
    ?>
        <input type="file" required name="admin_upload_software_mac" accept=".zip,.rar,.7zip" />
        <p>Mac link : <?php echo get_option('admin_upload_software_mac'); ?></p>
   <?php
}

function admin_upload_software_file_win_display()
{
    ?>
        <input type="file" required name="admin_upload_software_win" accept=".zip,.rar,.7zip" />
        <p>Window link : <?php echo get_option('admin_upload_software_win'); ?></p>
   <?php
}

add_action("admin_init", "admin_upload_software_settings_page");

function admin_upload_software_page()
{
    ?>
      <div class="wrap">

         <form method="post" enctype="multipart/form-data" action="options.php">
            <?php
                settings_fields("admin_upload_software");
                do_settings_sections("admin_upload_software");
                submit_button();
            ?>
         </form>
      </div>
   <?php
}

function menu_item()
{
    add_submenu_page("options-general.php", "Upload software settings", "Upload Software", "manage_options", "admin_upload_software", "admin_upload_software_page");
}

add_action("admin_menu", "menu_item");
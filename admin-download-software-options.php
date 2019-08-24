<?php
add_action('admin_menu', 'downloda_software_options_create_menu');

function downloda_software_options_create_menu()
{
    add_menu_page('Download Software Settings', 'Software Settings', 'administrator', __FILE__, 'downloda_software_options_settings_page');

    add_action('admin_init', 'register_downloda_software_options_settings');
}

function register_downloda_software_options_settings()
{
    register_setting('download-software-settings-group', 'mac_download_link');
    register_setting('download-software-settings-group', 'window_download_link');
}

function downloda_software_options_settings_page()
{
    ?>
<div class="wrap">
<h1>Download Software</h1>

<form method="post" action="options.php">
    <?php settings_fields('download-software-settings-group');?>
    <?php do_settings_sections('download-software-settings-group');?>
    <table class="form-table">
        <tr valign="top">
            <th scope="row">For Mac</th>
            <td>
                <input type="text" name="mac_download_link" value="<?php echo esc_attr(get_option('mac_download_link')); ?>" />
            </td>
        </tr>

        <tr valign="top">
            <th scope="row">Window</th>
            <td>
                <input type="text" name="window_download_link" value="<?php echo esc_attr(get_option('window_download_link')); ?>" />
            </td>
        </tr>
    </table>

    <?php submit_button();?>

</form>
</div>
<?php
}
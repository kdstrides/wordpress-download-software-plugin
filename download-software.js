jQuery(function ($)
{
    $(".download_software_form").on("submit", function (e)
    {
        $("#download-links").empty().hide();
        e.preventDefault();
        var ajax_url = download_software_ajax_script.ajaxurl;

        $.post(ajax_url, {
            data: $(this).serialize(),
            action: 'download_software_check_serial'
        }, function (res) {
            res = JSON.parse(res);
            $("#download-links").html(res.message).fadeIn();
        });
        return false;
    });
});
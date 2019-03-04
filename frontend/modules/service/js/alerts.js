;(function ($, window, document, undefined) {

    $(document).ready(function() {

        if( $.fn.jGrowl ) {
            // Get service alerts
            $.ajax({
                type: "POST",
                url: '/ASP/service/alerts',
                data: { action: 'retrieve', ajax: true },
                dataType: "json",
                timeout: 5000, // in milliseconds
                success: function (result) {
                    if (result.success === true) {
                        // jGrowl Notifications
                        $.each(result.message, function (index, value) {
                            $.jGrowl(value[1], {
                                header: value[0],
                                position: "bottom-right",
                                sticky: true,
                                click: function () {
                                    window.location.href = value[2];
                                }
                            });
                        });
                    }
                },
                error: function () {
                    // Ignore error
                }
            });
        }
    })

})(jQuery, window, document);
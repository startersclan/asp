;(function ($, window, document, undefined) {

    $(document).ready(function () {

        // Modal forms
        //noinspection JSUnresolvedVariable
        if( $.fn.dialog ) {
            $("#ajax-dialog").dialog({
                autoOpen: false,
                title: "Performing Backup",
                modal: true,
                width: "640",
                resizable: false,
                closeOnEscape: false,
                open: function(event, ui) {
                    $(".ui-dialog-titlebar-close", ui.dialog | ui).hide();
                }
            });
        }

        $("#backup").click(function(){
            // Show dialog form
            $("#ajax-dialog").dialog("option", { modal: true, position: 'center center' }).dialog("open");

            // Disable button
            $("#backup").prop('disabled', true);

            // Tell the backend to perform the backup
            $.post( "/ASP/database/backup", { action: "backup", ajax: true })
                .done(function( data ) {
                    // Parse response
                    var result = jQuery.parseJSON(data);
                    if (result.success == false) {
                        $('#jui-global-message')
                            .attr('class', 'alert error')
                            .html(result.message)
                            .slideDown(500);
                    }
                    else {
                        $('#jui-global-message')
                            .attr('class', 'alert success')
                            .html(result.message)
                            .slideDown(500);
                    }

                    // Close dialog
                    $("#ajax-dialog").dialog("close");
                });
        });

    });
})(jQuery, window, document);
;(function ($, window, document, undefined) {

    $(document).ready(function () {

        $form = $("#configForm").validate({
            ignoreTitle: true,
            rules: {
                cfg__battlespy_max_spm: {
                    required: true,
                    digits: true
                }
            },
            invalidHandler: function (form, validator) {
                var errors = validator.numberOfInvalids();
                if (errors) {
                    var message = errors == 1 ? 'You missed 1 field. It has been highlighted' : 'You missed ' + errors + ' fields. They have been highlighted';
                    $("#mws-validate-error").html(message).show();
                    $("html, body").animate({ scrollTop: 0 }, "fast");
                } else {
                    $("#mws-validate-error").hide();
                }
            }
        });

        // Enable popovers
        $("[rel=popover]").popover();

        // Spinners
        // noinspection JSUnresolvedVariable
        $.fn.spinner && $('.mws-spinner').spinner();

        // ===============================================
        // bind the Config form using 'ajaxForm'
        $('#configForm').ajaxForm({
            data: { ajax: true },
            beforeSubmit: function (arr, data, options)
            {
                $("#mws-validate-error").hide();
                $('#js_message').attr('class', 'alert loading').html('Submitting config settings...').slideDown(300);
                $("html, body").animate({ scrollTop: 0 }, "fast");
                return true;
            },
            success: function(response, statusText, xhr, $form) {
                // Parse the JSON response
                var result = jQuery.parseJSON(response);
                if (result.success == true)
                {
                    // Display our Success message, and ReDraw the table so we imediatly see our action
                    $('#js_message').attr('class', 'alert success').html('Success! Config saved successfully!');
                }
                else
                {
                    $('#js_message').attr('class', 'alert error').html('There was an error saving the configuration file. ' + result.message);
                }
            },
            error: function(request, status, error) {
                $("#mws-jui-dialog").html('<pre>' + request.responseText + '</pre>').dialog("open");
            },
            timeout: 5000
        });

    });
})(jQuery, window, document);
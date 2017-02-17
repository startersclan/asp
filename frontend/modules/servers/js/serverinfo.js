;(function( $, window, document, undefined ) {

    $(document).ready(function() {

        // IPv4 Address Validation
        jQuery.validator.addMethod('validIP', function(value) {
            var split = value.split('.');
            if (split.length != 4)
                return false;

            for (var i=0; i<split.length; i++) {
                var s = split[i];
                if (s.length==0 || isNaN(s) || s<0 || s>255)
                    return false;
            }
            return true;
        }, ' Invalid IP Address');


        // Data Tables
        if( $.fn.dataTable ) {
            $(".mws-datatable-fn").dataTable({
                sPaginationType: "full_numbers",
                bSort: false
            });
        }

        // Modal forms
        if( $.fn.dialog ) {
            $("#add-server-form").dialog({
                autoOpen: false,
                title: "Add New Server",
                modal: true,
                width: "640",
                resizable: false,
                buttons: [{
                    text: "Submit",
                    click: function () {
                        $(this).find('form#mws-validate').submit();
                    }
                }]
            });

            $("#mws-jui-dialog").dialog({
                autoOpen: false,
                title: "Confirm Delete Server",
                modal: true,
                width: "640",
                resizable: false
            });

            // Add New Server Click
            $("#add-new").click(function(e) {

                // For all modern browsers, prevent default behavior of the click
                e.preventDefault();

                // Set hidden input value
                $('input[name="action"]').val('add');

                // Form default values
                // Set form values
                $('input[name="serverName"]').val("");
                $('input[name="serverPrefix"]').val("");
                $('input[name="serverIp"]').val("");
                $('input[name="serverPort"]').val(16567);
                $('input[name="serverQueryPort"]').val(29900);

                // Show dialog form
                $("#add-server-form").dialog("option", {
                    modal: true
                }).dialog("open");

                // Just to be sure, older IE's needs this
                return false;
            });
        }

        // Ajax and form Validation
        $("#mws-validate").ajaxForm({
            beforeSubmit: function (arr, data, options)
            {
                $("#mws-validate-error").hide();
                return true;
            },
            success: function (response, statusText, xhr, $form) {
                // Parse the JSON response
                var result = jQuery.parseJSON(response);
                if (result.success == true) {
                    location.reload();
                }
                else {
                    $('#jui-message').attr('class', 'alert error').html(result.message).slideDown(500);
                }
            },
            error: function(request, status, error) {
                $('#jui-message').attr('class', 'alert error').html('AJAX Error! Please check the console log.').slideDown(500);
            },
            timeout: 5000
        }).validate({
            rules: {
                serverIp: {
                    required: true,
                    validIP: true
                },
                serverPort: {
                    required: true,
                    min: 1,
                    max: 65535
                },
                serverQueryPort: {
                    required: true,
                    min: 1,
                    max: 65535
                }
            },
            invalidHandler: function (form, validator) {
                var errors = validator.numberOfInvalids();
                if (errors) {
                    var message = errors == 1 ? 'You missed 1 field. It has been highlighted' : 'You missed ' + errors + ' fields. They have been highlighted';
                    $("#mws-validate-error").html(message).show();
                } else {
                    $("#mws-validate-error").hide();
                }
            }
        });

        // Spinners
        $.fn.spinner && $('.mws-spinner').spinner();

        // Tooltips
        $.fn.tooltip && $('[rel="tooltip"]').tooltip();

        // Edit Server Row Button
        $("[id^=edit-btn-]").click(function(e) {

            // For all modern browsers, prevent default behavior of the click
            e.preventDefault();

            // Extract the server ID
            var sid = $(this).attr('id').split("-");
            var id = sid[sid.length-1];

            // Set hidden input values
            $('input[name="action"]').val('edit');
            $('input[name="serverId"]').val(id);

            // Set form values
            $('input[name="serverName"]').val($('#tr-server-' + id).find('td:eq(2)').html());
            $('input[name="serverPrefix"]').val($('#tr-server-' + id).find('td:eq(3)').html());
            $('input[name="serverIp"]').val($('#tr-server-' + id).find('td:eq(4)').html());
            $('input[name="serverPort"]').val($('#tr-server-' + id).find('td:eq(5)').html());
            $('input[name="serverQueryPort"]').val($('#tr-server-' + id).find('td:eq(6)').html());

            // Show dialog form
            $("#add-server-form").dialog("option", {
                modal: true
            }).dialog("open");

            // Just to be sure, older IE's needs this
            return false;
        });

        // Un-Authorize Server Row Button
        $("[id^=unauth-btn-]").click(function(e) {

            // For all modern browsers, prevent default behavior of the click
            e.preventDefault();

            // Extract the server ID
            var sid = $(this).attr('id').split("-");
            var id = sid[sid.length-1];

            // Push the request
            $.post( "/ASP/servers/authorize", { action: "unauth", servers: [id] })
                .done(function( data ) {
                    // Parse response
                    var result = jQuery.parseJSON(data);
                    if (result.success == false) {
                        $('#jui-message').attr('class', 'alert error').html(result.message).slideDown(500);
                    }
                    else {
                        // Update html and button displays
                        $('#tr-server-' + id).find('td:eq(8)').html('No');
                        $('#unauth-btn-' + id).hide();
                        $('#auth-btn-' + id).show();
                    }
                });

            // Just to be sure, older IE's needs this
            return false;
        });

        // Authorize Server Row Button
        $("[id^=auth-btn-]").click(function(e) {

            // For all modern browsers, prevent default behavior of the click
            e.preventDefault();

            // Extract the server ID
            var sid = $(this).attr('id').split("-");
            var id = sid[sid.length-1];

            // Push the request
            $.post( "/ASP/servers/authorize", { action: "auth", servers: [id] })
                .done(function( data ) {
                    // Parse response
                    var result = jQuery.parseJSON(data);
                    if (result.success == false) {
                        $('#jui-message').attr('class', 'alert error').html(result.message).slideDown(500);
                    }
                    else {
                        // Update html and button displays
                        $('#tr-server-' + id).find('td:eq(8)').html('Yes');
                        $('#auth-btn-' + id).hide();
                        $('#unauth-btn-' + id).show();
                    }
                });

            // Just to be sure, older IE's needs this
            return false;
        });

        // Delete server Row Button
        $("[id^=delete-btn-]").click(function(e) {

            // For all modern browsers, prevent default behavior of the click
            e.preventDefault();

            // Skip disabled buttons
            if ($(this).attr('disabled') == 'disabled')
                return false;

            // Extract the server ID
            var sid = $(this).attr('id').split("-");
            var id = sid[sid.length-1];

            // Always have the user confirm his action here!
            var name = $('#tr-server-' + id).find('td:eq(2)').html();

            // Show dialog form
            $("#mws-jui-dialog")
                .html('Are you sure you want to delete the server "' + name + '"? All player histories and round histories made by this server will also be removed!')
                .dialog("option", {
                    modal: true,
                    buttons: [
                        {
                            text: "Confirm",
                            class: "btn btn-danger",
                            click: function () {
                                delete_servers([id]);
                                $(this).dialog("close");
                            }
                        },
                        {
                            text: "Cancel",
                            click: function () {
                                $(this).dialog("close");
                            }
                        }
                    ]
                }).dialog("open");

            // Just to be sure, older IE's needs this
            return false;
        });

        // Delete Selected Click
        $("#delete-selected").click(function(e) {

            // For all modern browsers, prevent default behavior of the click
            e.preventDefault();

            // Get all checked
            var serverIds = $('input[type=checkbox]:checked').map(function() {
                // Extract the server ID
                var sid = $(this).attr('id').split("-");
                return sid[sid.length-1];
            }).get();

            // Is anything selected?
            if (serverIds.length < 1)
                return false;

            // Show dialog form
            $("#mws-jui-dialog")
                .html('Are you sure you want to delete the selected servers? All player histories and round histories made by this server will also be removed!')
                .dialog("option", {
                    modal: true,
                    buttons: [
                        {
                            text: "Confirm",
                            class: "btn btn-danger",
                            click: function () {
                                delete_servers(serverIds);
                                $(this).dialog("close");
                            }
                        },
                        {
                            text: "Cancel",
                            click: function () {
                                $(this).dialog("close");
                            }
                        }
                    ]
                }).dialog("open");

            // Just to be sure, older IE's needs this
            return false;
        });

        // Un-Authorized Selected Click
        $("#unauth-selected").click(function(e) {

            // For all modern browsers, prevent default behavior of the click
            e.preventDefault();

            // Get all checked
            var checkValues = $('input[type=checkbox]:checked').map(function() {
                // Extract the server ID
                var sid = $(this).attr('id').split("-");
                return sid[sid.length-1];
            }).get();

            // Push the request
            $.post( "/ASP/servers/authorize", { action: "unauth", servers: checkValues })
                .done(function( data ) {

                    // Parse response
                    var result = jQuery.parseJSON(data);
                    if (result.success == false) {
                        $('#jui-message').attr('class', 'alert error').html(result.message).slideDown(500);
                    }
                    else {
                        // Remove each row
                        $.each(checkValues, function (key, value) {
                            // Update html and button displays
                            $('#tr-server-' + value).find('td:eq(8)').html('No');
                            $('#auth-btn-' + value).show();
                            $('#unauth-btn-' + value).hide();
                        });
                    }
                });

            // Just to be sure, older IE's needs this
            return false;
        });

        // Authorized Selected Click
        $("#auth-selected").click(function(e) {

            // For all modern browsers, prevent default behavior of the click
            e.preventDefault();

            // Get all checked
            var checkValues = $('input[type=checkbox]:checked').map(function() {
                // Extract the server ID
                var sid = $(this).attr('id').split("-");
                return sid[sid.length-1];
            }).get();

            // Push the request
            $.post( "/ASP/servers/authorize", { action: "auth", servers: checkValues })
                .done(function( data ) {

                    // Parse response
                    var result = jQuery.parseJSON(data);
                    if (result.success == false) {
                        $('#jui-message').attr('class', 'alert error').html(result.message).slideDown(500);
                    }
                    else {
                        // Remove each row
                        $.each(checkValues, function (key, value) {
                            // Update html and button displays
                            $('#tr-server-' + value).find('td:eq(8)').html('Yes');
                            $('#unauth-btn-' + value).show();
                            $('#auth-btn-' + value).hide();
                        });
                    }
                });

            // Just to be sure, older IE's needs this
            return false;
        });

        // Refresh Click
        $("#refresh").click(function(e) {

            // For all modern browsers, prevent default behavior of the click
            e.preventDefault();

            // Reload page (temporary).
            location.reload();

            // Just to be sure, older IE's needs this
            return false;
        });

        function delete_servers(ids)
        {
            // Push the request
            $.post( "/ASP/servers/delete", { action: "delete", servers: ids })
                .done(function( data ) {

                    // Parse response
                    var result = jQuery.parseJSON(data);
                    if (result.success == false) {
                        $('#jui-message').attr('class', 'alert error').html(result.message).slideDown(500);
                    }
                    else {
                        // Remove each row
                        $.each(ids, function (key, value) {
                            $('#tr-server-' + value).remove();
                        });
                    }
                });
        }

    });

}) (jQuery, window, document);
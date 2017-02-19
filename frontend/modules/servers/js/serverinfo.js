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
        var Table = $(".mws-datatable-fn").DataTable({
            sPaginationType: "full_numbers",
            bSort: false
        });

        // Modal forms
        //noinspection JSUnresolvedVariable
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

                // Set form default values
                $('input[name="serverName"]').val("");
                $('input[name="serverPrefix"]').val("");
                $('input[name="serverIp"]').val("");
                $('input[name="serverPort"]').val(16567);
                $('input[name="serverQueryPort"]').val(29900);

                // Hide previous errors
                $("#mws-validate-error").hide();

                // Show dialog form
                $("#add-server-form").dialog("option", {
                    title: "Add New Server",
                    modal: true
                }).dialog("open");

                // Just to be sure, older IE's needs this
                return false;
            });
        }

        // Ajax and form Validation
        //noinspection JSJQueryEfficiency
        $("#mws-validate").validate({
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

        //noinspection JSJQueryEfficiency
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
                    var id = result.serverId;
                    if (result.mode == 'add') {
                        // Add server to table
                        //noinspection JSUnresolvedFunction
                        var rowNode = Table.row.add([
                            '<td class="checkbox-column"><input id="server-' + id + '" type="checkbox"></td>',
                            result.serverId,
                            result.serverName,
                            result.serverPrefix,
                            result.serverIp,
                            result.serverPort,
                            result.serverQueryPort,
                            0,
                            'Yes',
                            ' <span class="btn-group"> \
                                <a id="edit-btn-' + id + '" href="#"  rel="tooltip" title="Edit Server" class="btn btn-small"><i class="icon-pencil"></i></a> \
                                <a id="auth-btn-' + id + '" href="#" rel="tooltip" title="Authorize Server" class="btn btn-small" style="display: none"><i class="icon-ok"></i></a> \
                                <a id="unauth-btn-' + id + '" href="#" rel="tooltip" title="Un-Authorize Server" class="btn btn-small" ><i class="icon-unlink"></i></a> \
                                <a id="delete-btn-' + id + '" href="#" class="btn btn-small" rel="tooltip" title="Delete Server" ><i class="icon-trash"></i></a> \
                            </span>'
                        ]).draw().node();

                        $( rowNode ).attr('id', 'tr-server-' + id);
                    }
                    else if (result.mode == 'update') {
                        $('#tr-server-' + id).find('td:eq(2)').html(result.serverName);
                        $('#tr-server-' + id).find('td:eq(3)').html(result.serverPrefix);
                        $('#tr-server-' + id).find('td:eq(4)').html(result.serverIp);
                        $('#tr-server-' + id).find('td:eq(5)').html(result.serverPort);
                        $('#tr-server-' + id).find('td:eq(6)').html(result.serverQueryPort);
                    }

                    // Close dialog
                    $("#add-server-form").dialog("close");
                }
                else {
                    $('#jui-message').attr('class', 'alert error').html(result.message).slideDown(500);
                }
            },
            error: function(request, status, error) {
                $('#jui-message').attr('class', 'alert error').html('AJAX Error! Please check the console log.').slideDown(500);
            },
            timeout: 5000
        });

        // Spinners
        //noinspection JSUnresolvedVariable
        $.fn.spinner && $('.mws-spinner').spinner();

        // Tooltips
        //noinspection JSUnresolvedVariable
        $.fn.tooltip && $('[rel="tooltip"]').tooltip({ "delay": { show: 500, hide: 0 } });

        // Bind tooltips to new rows added from Ajax
        $('.mws-datatable-fn').on( 'draw.dt', function () {
            //noinspection JSUnresolvedVariable
            $.fn.tooltip && $('[rel="tooltip"]').tooltip({ "delay": { show: 500, hide: 0 } });
        });

        // Row Button Clicks
        $(document).on('click', 'a.btn-small', function(e) {

            // For all modern browsers, prevent default behavior of the click
            e.preventDefault();

            // Extract the server ID
            var sid = $(this).attr('id').split("-");
            var action = sid[0];
            var id = sid[sid.length-1];

            if (action == 'edit') {

                // Set hidden input values
                $('input[name="action"]').val('edit');
                $('input[name="serverId"]').val(id);

                // Set form values
                $('input[name="serverName"]').val($('#tr-server-' + id).find('td:eq(2)').html());
                $('input[name="serverPrefix"]').val($('#tr-server-' + id).find('td:eq(3)').html());
                $('input[name="serverIp"]').val($('#tr-server-' + id).find('td:eq(4)').html());
                $('input[name="serverPort"]').val($('#tr-server-' + id).find('td:eq(5)').html());
                $('input[name="serverQueryPort"]').val($('#tr-server-' + id).find('td:eq(6)').html());

                // Hide previous errors
                $("#mws-validate-error").hide();

                // Show dialog form
                $("#add-server-form").dialog("option", {
                    title: 'Update Server Info',
                    modal: true
                }).dialog("open");
            }
            else if (action == 'unauth') {
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
            }
            else if (action == 'auth') {
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
            }
            else if (action == 'delete') {
                // Skip disabled buttons
                if ($(this).attr('disabled') == 'disabled')
                    return false;

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
            }

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
                            Table.row( $('#tr-server-' + value) ).remove().draw();
                        });
                    }
                });
        }

    });

}) (jQuery, window, document);
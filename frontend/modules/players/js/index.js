;(function( $, window, document, undefined ) {

    $(document).ready(function() {

        /**
         * Extracts the filename, without extension from a path
         *
         * @param extension The filepath
         * @returns {string} Returns the filename, without extension
         */
        String.prototype.filename = function(extension){
            var s= this.replace(/\\/g, '/');
            s= s.substring(s.lastIndexOf('/')+ 1);
            return extension? s.replace(/[?#].+$/, ''): s.split('.')[0];
        };

        // Data Tables
        var Table = $(".mws-datatable-fn").DataTable({
            pagingType: "full_numbers",
            processing: false,
            serverSide: true,
            ajax: {
                url: "/ASP/players/list",
                type: "POST"
            },
            columns: [
                { "data": "id" },
                { "data": "rank" },
                { "data": "name" },
                { "data": "score" },
                { "data": "country" },
                { "data": "joined" },
                { "data": "online" },
                { "data": "clan" },
                { "data": "permban" },
                { "data": "actions" }
            ],
            columnDefs: [
                { "searchable": false, "targets": 1 },
                { "searchable": false, "targets": 3 },
                { "searchable": false, "targets": 5 },
                { "searchable": false, "targets": 6 },
                { "searchable": false, "targets": 7 },
                { "searchable": false, "targets": 8 },
                { "searchable": false, "orderable": false, "targets": 9 }
            ]
        });

        // Ajax and form Validation
        //noinspection JSJQueryEfficiency
        var validator = $("#mws-validate").validate({
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

        // Modal forms
        //noinspection JSUnresolvedVariable
        if( $.fn.dialog ) {
            $("#add-player-form").dialog({
                autoOpen: false,
                title: "Add New Player",
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
                title: "Confirm Delete Player",
                modal: true,
                width: "640",
                resizable: false
            });

            // Add New Server Click
            $("#add-new").click(function(e) {

                // For all modern browsers, prevent default behavior of the click
                e.preventDefault();

                // Hide previous errors
                $("#mws-validate-error").hide();
                validator.resetForm();

                // Set hidden input value
                $('input[name="action"]').val('add');

                // Set form default values
                $('input[name="playerName"]').val("");
                $('input[name="playerPassword"]').val("").rules('add', {
                    required: true   // overwrite an existing rule
                });
                $('#passwordLabel').html('Password');
                $("#rankSelect").val(0);

                // Show dialog form
                $("#add-player-form").dialog("option", {
                    modal: true,
                    title: "Create New Player"
                }).dialog("open");

                // Just to be sure, older IE's needs this
                return false;
            });
        }

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

                    // Reload the table
                    Table.ajax.reload();

                    // Close dialog
                    $("#add-player-form").dialog("close");
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

        /* Chosen Select Box Plugin */
        $.fn.select2 && $("select.mws-select2").select2();

        // Tooltips
        // Bind tooltips to new rows added from Ajax
        $('.mws-datatable-fn').on( 'draw.dt', function () {
            //noinspection JSUnresolvedVariable
            $.fn.tooltip && $('[rel="tooltip"]').tooltip({ "delay": { show: 500, hide: 0 } });
        });

        // Refresh Click
        $("#refresh").click(function(e) {

            // For all modern browsers, prevent default behavior of the click
            e.preventDefault();

            // Reload page (temporary).
            Table.ajax.reload();

            // Just to be sure, older IE's needs this
            return false;
        });

        // Row Button Clicks
        $(document).on('click', 'a.btn-small', function(e) {

            // For all modern browsers, prevent default behavior of the click
            e.preventDefault();

            // Extract the server ID
            var sid = $(this).attr('id').split("-");
            var action = sid[0];
            var id = sid[sid.length-1];

            // Always have the user confirm his action here!
            var name = $(this).closest('tr').find('td:eq(2) a').html();

            if (action == 'edit') {

                // Hide previous errors
                $("#mws-validate-error").hide();
                validator.resetForm();

                // Set hidden input values
                $('input[name="action"]').val('edit');
                $('input[name="playerId"]').val(id);

                // Set form values
                $('input[name="playerName"]').val(name);
                $('input[name="playerPassword"]').val("").rules('remove', 'required');
                $('#passwordLabel').html('Update Password');

                // Set player rank
                var rankHtml = $(this).closest('tr').find('td:eq(1)').html();
                var rank =  rankHtml.filename().split('_')[1];
                $("#rankSelect").val(rank);

                // Select users country
                var cntry = $(this).closest('tr').find('td:eq(4)').html();
                $("select.mws-select2").val(cntry).change();

                // Show dialog form
                $("#add-player-form").dialog("option", {
                    modal: true,
                    title: 'Update Existing Player'
                }).dialog("open");
            }
            else if (action == 'unban') {
                // Push the request
                $.post( "/ASP/players/authorize", { action: "unban", playerId: id })
                    .done(function( data ) {
                        // Parse response
                        var result = jQuery.parseJSON(data);
                        if (result.success == false) {
                            $('#jui-message').attr('class', 'alert error').html(result.message).slideDown(500);
                        }
                        else {
                            // Update html and button displays
                            $('#unban-btn-' + id).closest('tr').find('td:eq(8)').html('<font color="green">No</font>');
                            $('#unban-btn-' + id).hide();
                            $('#ban-btn-' + id).show();
                        }
                    });
            }
            else if (action == 'ban') {
                // Push the request
                $.post( "/ASP/players/authorize", { action: "ban", playerId: id })
                    .done(function( data ) {
                        // Parse response
                        var result = jQuery.parseJSON(data);
                        if (result.success == false) {
                            $('#jui-message').attr('class', 'alert error').html(result.message).slideDown(500);
                        }
                        else {
                            // Update html and button displays
                            $('#unban-btn-' + id).closest('tr').find('td:eq(8)').html('<font color="red">Yes</font>');
                            $('#unban-btn-' + id).show();
                            $('#ban-btn-' + id).hide();
                        }
                    });
            }
            else if (action == 'delete') {
                // Show dialog form
                $("#mws-jui-dialog")
                    .html('Are you sure you want to delete player "' + name + '"? This action cannot be undone.')
                    .dialog("option", {
                        modal: true,
                        buttons: [
                            {
                                text: "Confirm",
                                class: "btn btn-danger",
                                click: function () {

                                    $.post( "/ASP/players/delete", { action: "delete", playerId: id })
                                        .done(function( data ) {
                                            // Parse response
                                            var result = jQuery.parseJSON(data);
                                            if (result.success == false) {
                                                $('#jui-message').attr('class', 'alert error').html(result.message).slideDown(500);
                                            }
                                            else {
                                                // Update html and button displays
                                                Table.row( $(this).closest('tr') ).remove().draw();
                                            }
                                        });

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

    });

}) (jQuery, window, document);
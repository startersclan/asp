;(function( $, window, document, undefined ) {

    $(document).ready(function() {

        // Data Tables
        var Table = $(".mws-datatable-fn").DataTable({
            pagingType: "full_numbers"
        });

        // Ajax and form Validation
        //noinspection JSJQueryEfficiency
        var validator = $("#mws-validate").validate({
            rules: {
                awardCode: {
                    required: true,
                    minlength: 1,
                    maxlength: 6
                },
                awardId: {
                    required: true,
                    min: 1000000,
                    max: 3999999
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

        // Modal forms
        //noinspection JSUnresolvedVariable
        if( $.fn.dialog ) {
            $("#editor-form").dialog({
                autoOpen: false,
                title: "Add New Award",
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
                title: "Confirm Delete Award",
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
                $('input[name="originalId"]').val(0);

                // Set form default values
                $('input[name="awardName"]').val("");
                $('input[name="awardCode"]').val("");
                $('input[name="awardId"]').val("");


                $("#awardType").val(0);
                $("#awardBackend").val(0);

                // Show dialog form
                $("#editor-form").dialog("option", {
                    modal: true,
                    title: "Create New Award"
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
                    var id = result.id;
                    var rowNode;
                    var backend = (parseInt(result.backend) == 1) ? 'Yes' : 'No';

                    if (result.mode == 'add') {
                        // Add award to table
                        //noinspection JSUnresolvedFunction
                        rowNode = Table.row.add([
                            result.id,
                            result.name,
                            result.code,
                            typeToString(result.type),
                            backend,
                            '<span class="btn-group"> \
                                <a id="edit-' + id + '" href="#"  rel="tooltip" title="Edit Award" class="btn btn-small"><i class="icon-pencil"></i></a> \
                                <a id="delete-' + id + '" href="#" class="btn btn-small" rel="tooltip" title="Delete Award" ><i class="icon-trash"></i></a> \
                            </span>'
                        ]).draw().node();

                        $( rowNode ).attr('id', 'tr-award-' + id);
                    }
                    else if (result.mode == 'edit') {
                        rowNode = $('#tr-award-' + id);
                        rowNode.find('td:eq(0)').html(result.id);
                        rowNode.find('td:eq(1)').html(result.name);
                        rowNode.find('td:eq(2)').html(result.code);
                        rowNode.find('td:eq(3)').html(typeToString(result.type));
                        rowNode.find('td:eq(4)').html(backend);
                    }

                    // Close dialog
                    $("#editor-form").dialog("close");
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

        // Tooltips
        // Bind tooltips to new rows added from Ajax
        $('.mws-datatable-fn').on( 'draw.dt', function () {
            //noinspection JSUnresolvedVariable
            $.fn.tooltip && $('[rel="tooltip"]').tooltip({ "delay": { show: 500, hide: 0 } });
        });

        // Spinners
        //noinspection JSUnresolvedVariable
        $.fn.spinner && $('.mws-spinner').spinner();

        // Refresh Click
        $("#refresh").click(function(e) {

            // For all modern browsers, prevent default behavior of the click
            e.preventDefault();

            // Reload page (temporary).
            location.reload();

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
            var tr = $(this).closest('tr');
            var name = tr.find('td:eq(1)').html();
            var code = tr.find('td:eq(2)').html();
            var backend = tr.find('td:eq(4)').html();

            if (action == 'edit') {

                // Hide previous errors
                $("#mws-validate-error").hide();
                validator.resetForm();

                // Set hidden input value
                $('input[name="action"]').val('edit');
                $('input[name="originalId"]').val(id);

                // Set form default values
                $('input[name="awardName"]').val(name);
                $('input[name="awardCode"]').val(code);
                $('input[name="awardId"]').val(id);
                $("#awardBackend").val( (backend == 'Yes') ? 1 : 0 );

                // Set award type
                var awardType = $("#awardType");
                switch (id[0])
                {
                    case "1":
                        awardType.val(1);
                        break;
                    case "2":
                        awardType.val(2);
                        break;
                    case "3":
                        awardType.val(0);
                        break;
                }

                // Show dialog form
                $("#editor-form").dialog("option", {
                    modal: true,
                    title: 'Update Existing Award'
                }).dialog("open");
            }
            else if (action == 'delete') {
                // Show dialog form
                $("#mws-jui-dialog")
                    .html('Are you sure you want to delete award "' + name + '"? This action cannot be undone.')
                    .dialog("option", {
                        modal: true,
                        buttons: [
                            {
                                text: "Confirm",
                                class: "btn btn-danger",
                                click: function () {

                                    $.post( "/ASP/gamedata/deleteAward", { action: "delete", awardId: id })
                                        .done(function( data ) {
                                            // Parse response
                                            var result = jQuery.parseJSON(data);
                                            if (result.success == false) {
                                                $('#jui-message').attr('class', 'alert error').html(result.message).slideDown(500);
                                            }
                                            else {
                                                // Update html and button displays
                                                Table.row( tr ).remove().draw();
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

    function typeToString(type)
    {
        switch (parseInt(type))
        {
            default:
            case 0:
                return "Ribbon";
            case 1:
                return "Badge";
            case 2:
                return "Medal";
        }
    }

}) (jQuery, window, document);
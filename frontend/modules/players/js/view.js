;(function ($, window, document, undefined) {

    $(document).ready(function () {

        // Variables
        var playerId = parseInt($("#playerCurrentId").html());
        var playerRank = parseInt($("#playerCurrentRank").html());
        var playerName = $("#changeableName").html();
        var playerIso = $("#playerCurrentIso").html();

        // Data Tables
        $(".mws-table").DataTable({
            bPaginate: false,
            bFilter: false,
            bInfo: false,
            order: [[ 1, "desc" ]],
            columnDefs: [
                { "orderable": false, "targets": 0 }
            ]
        }).on( 'draw.dt', function () {
            //noinspection JSUnresolvedVariable
            $.fn.tooltip && $('[rel="tooltip"]').tooltip({ "delay": { show: 500, hide: 0 } });
        });

        // Modal forms
        // noinspection JSUnresolvedVariable
        if( $.fn.dialog ) {
            $("#edit-player-form").dialog({
                autoOpen: false,
                title: "Update Player",
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

            // Add New Server Click
            $("#edit-player").click(function(e) {

                // For all modern browsers, prevent default behavior of the click
                e.preventDefault();

                // Hide previous errors
                $("#jui-message").hide();

                // Set form default values
                $('input[name="playerName"]').val(playerName);
                $("#rankSelect").val(playerRank);
                $("select.mws-select2").val(playerIso).change();

                // Show dialog form
                $("#edit-player-form").dialog("option", {
                    modal: true,
                    title: "Update Player"
                }).dialog("open");

                // Just to be sure, older IE's needs this
                return false;
            });
        }

        // Ajax and form Validation
        //noinspection JSJQueryEfficiency
        var validator = $("#mws-validate").validate({
            rules: {
                playerName: {
                    required: true,
                    minlength: 3,
                    maxlength: 32
                }
            },
            invalidHandler: function (form, validator) {
                var errors = validator.numberOfInvalids();
                if (errors) {
                    var message = errors == 1 ? 'You missed 1 field. It has been highlighted' : 'You missed ' + errors + ' fields. They have been highlighted';
                    $("#mws-validate-error").html(message).show();
                    $('#jui-message').hide();
                } else {
                    $("#mws-validate-error").hide();
                }
            }
        });

        // Ajax Form
        // noinspection JSJQueryEfficiency
        $("#mws-validate").ajaxForm({
            data: {
                ajax: true,
                playerId: playerId
            },
            beforeSubmit: function (arr, data, options) {
                $('#jui-message').attr('class', 'alert loading').html("Submitting form data...").slideDown(200);
                return true;
            },
            success: function (response, statusText, xhr, $form) {
                // Parse the JSON response
                var result = jQuery.parseJSON(response);
                if (result.success == true) {
                    // Update variables
                    playerName = result.name;
                    playerRank = result.rank;
                    playerIso = result.iso;

                    // Update html
                    $("#playerCurrentRank").html(result.rank);
                    $("#playerCurrentName").html(result.name);
                    $("#playerCurrentIso").html(result.iso);

                    $("#changeableName").html(result.name);
                    $("#changeableRank").html(result.rankName);
                    $("#rankIcon").attr('src', "/ASP/frontend/images/ranks/rank_" + result.rank + ".gif");
                    $("#flag").attr('src', "/ASP/frontend/images/flags/" + result.iso + ".png");

                    // Close dialog
                    $("#edit-player-form").dialog("close");
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
        //noinspection JSUnresolvedVariable
        $.fn.tooltip && $('[rel="tooltip"]').tooltip({ "delay": { show: 500, hide: 0 } });

        // Enable popovers
        $("[rel=popover]").popover({html: true});

        // jQuery-UI Tabs
        // noinspection JSUnresolvedVariable
        $.fn.tabs && $(".mws-tabs").tabs();

        // Chosen Select Box Plugin
        // noinspection JSUnresolvedVariable
        $.fn.select2 && $("select.mws-select2").select2();

        // Ban Button Click
        $("#ban-player").click(function(e) {

            // For all modern browsers, prevent default behavior of the click
            e.preventDefault();

            $.post( "/ASP/players/authorize", { ajax: true, action: "ban", playerId: playerId })
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
                        // Update buttons
                        $("#ban-player").hide();
                        $("#unban-player").show();

                        // Update account status
                        $("#status").html("Banned").css('color', 'red');
                    }
                });

            // Just to be sure, older IE's needs this
            return false;
        });

        // UnBan Button Click
        $("#unban-player").click(function(e) {

            // For all modern browsers, prevent default behavior of the click
            e.preventDefault();

            $.post( "/ASP/players/authorize", { ajax: true, action: "unban", playerId: playerId })
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
                        // Update buttons
                        $("#ban-player").show();
                        $("#unban-player").hide();

                        // Update account status
                        $("#status").html("Active").css('color', 'green');
                    }
                });

            // Just to be sure, older IE's needs this
            return false;
        });

    });
})(jQuery, window, document);
;(function( $, window, document ) {

    $(document).ready(function() {

        // Ajax and form Validation
        // noinspection JSJQueryEfficiency
        var validator = $("#mws-validate").validate({
            rules: {
                mapName: {
                    required: true,
                    minlength: 3,
                    maxlength: 48
                }
            },
            invalidHandler: function (form, validator) {
                var errors = validator.numberOfInvalids();
                if (errors) {
                    var message = errors === 1 ? 'You missed 1 field. It has been highlighted' : 'You missed ' + errors + ' fields. They have been highlighted';
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
                title: "Edit Display Name",
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
        }

        //noinspection JSJQueryEfficiency
        $("#mws-validate").ajaxForm({
            data: { ajax: true },
            beforeSubmit: function () {
                $("#mws-validate-error").hide();
                return true;
            },
            success: function (response) {
                // Parse the JSON response
                var result = jQuery.parseJSON(response);
                if (result.success === true) {

                    // Update name
                    $("#currentMapName").html(result.displayName);

                    // Close dialog
                    $("#editor-form").dialog("close");
                }
                else {
                    $('#jui-message').attr('class', 'alert error').html(result.message).slideDown(500);

                    // Close dialog
                    $("#editor-form").dialog("close");
                }
            },
            error: function() {
                $('#jui-message').attr('class', 'alert error').html('AJAX Error! Please check the console log.').slideDown(500);
            },
            timeout: 10000
        });

        // A custom label formatter used by several of the plots
        function labelFormatter(label, series) {
            // noinspection JSCheckFunctionSignatures
            return "<div style='font-size:8pt; text-align:center; padding:2px; color:white;'>" + Math.round(series.percent) + "%</div>";
        }

        // Kill Death Ratio Chart
        if ($("#mws-pie-1").length > 0) {
            // noinspection JSUnresolvedVariable
            $.plot($("#mws-pie-1"), armyData, {
                series: {
                    pie: {
                        highlight: {
                            opacity: 0.2
                        },
                        stroke: {
                            width: 1
                        },
                        label: {
                            show: true,
                            radius: 3/4,
                            formatter: labelFormatter,
                            background: {
                                opacity: 0.5,
                                color: '#000'
                            }
                        },
                        show: true
                    }
                },
                legend: {
                    show: true
                },
                grid: {
                    hoverable: true
                },
                tooltip: true,
                tooltipOpts: {
                    content: "Total Wins: %n",
                    defaultTheme: false,
                    cssClass: 'flotTip',
                    show: true
                }
            });
        }

        // Row Button Clicks
        $("#edit-map").on('click', function(e) {

            var name = $("#currentMapName").html();

            // Hide previous errors
            $("#mws-validate-error").hide();
            validator.resetForm();

            // Set form default values
            $('input[name="mapName"]').val(name);

            // Show dialog form
            $("#editor-form").dialog("option", {
                modal: true,
                title: "Edit Map Name"
            }).dialog("open");

            // Just to be sure, older IE's needs this
            return false;
        });

    });

}) (jQuery, window, document);
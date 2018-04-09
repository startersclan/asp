;(function ($, window, document, undefined) {

    $(document).ready(function () {

        // Get server ID
        var serverId = parseInt( $("#serverId").html() );

        // Query server online status
        queryServer();

        if( $.fn.button ) {
            $("#mws-ui-button-radio").buttonset();
        }

        // Refresh Click
        $("#refresh").click(function(e) {

            // For all modern browsers, prevent default behavior of the click
            e.preventDefault();

            // The a element does not have a property disabled. So defining one won't
            // affect any event handlers you may have attached to it. Therefore, we use data instead
            if ($(this).data('disabled')) return;

            // Show client a message
            $('#jui-global-message')
                .attr('class', 'alert loading')
                .html("Fetching Server Status and Information...")
                .slideDown(200);

            // Reload page.
            queryServer();

            // Just to be sure, older IE's needs this
            return false;
        });

        // Authorize Click
        $("#auth-server").click(function(e) {

            // For all modern browsers, prevent default behavior of the click
            e.preventDefault();

            // Push the request
            $.post( "/ASP/servers/authorize", { action: "auth", ajax: true, servers: [serverId] })
                .done(function( data ) {

                    // Parse response
                    var result = jQuery.parseJSON(data);
                    if (result.success == false) {
                        $('#jui-global-message')
                            .attr('class', 'alert error')
                            .html(result.message)
                            .append('<span class="close-bt"></span>')
                            .slideDown(500);
                    }
                    else {
                        $("#auth-server").hide();
                        $("#unauth-server").show();
                    }
                })
                .fail(function( jqXHR ) {
                    var result = jQuery.parseJSON(jqXHR.responseText);
                    if (result != null)
                    {
                        $('#jui-global-message')
                            .attr('class', 'alert error')
                            .html(result.message)
                            .append('<span class="close-bt"></span>')
                            .slideDown(500);
                    }
                    else
                    {
                        $('#jui-global-message')
                            .attr('class', 'alert error')
                            .html("An Error Occurred. Please check the ASP error log for details.")
                            .append('<span class="close-bt"></span>')
                            .slideDown(500);
                    }
                });

            // Just to be sure, older IE's needs this
            return false;
        });

        // Un-Authorize Click
        $("#unauth-server").click(function(e) {

            // For all modern browsers, prevent default behavior of the click
            e.preventDefault();

            // Push the request
            $.post( "/ASP/servers/authorize", { action: "unauth", ajax: true, servers: [serverId] })
                .done(function( data ) {

                    // Parse response
                    var result = jQuery.parseJSON(data);
                    if (result.success == false) {
                        $('#jui-global-message')
                            .attr('class', 'alert error')
                            .html(result.message)
                            .append('<span class="close-bt"></span>')
                            .slideDown(500);
                    }
                    else {
                        $("#auth-server").show();
                        $("#unauth-server").hide();
                    }
                })
                .fail(function( jqXHR ) {
                    var result = jQuery.parseJSON(jqXHR.responseText);
                    if (result != null)
                    {
                        $('#jui-global-message')
                            .attr('class', 'alert error')
                            .html(result.message)
                            .append('<span class="close-bt"></span>')
                            .slideDown(500);
                    }
                    else
                    {
                        $('#jui-global-message')
                            .attr('class', 'alert error')
                            .html("An Error Occurred. Please check the ASP error log for details.")
                            .append('<span class="close-bt"></span>')
                            .slideDown(500);
                    }
                });

            // Just to be sure, older IE's needs this
            return false;
        });

        // Plasma Click
        $("#plasma-server").click(function(e) {

            // For all modern browsers, prevent default behavior of the click
            e.preventDefault();

            // Push the request
            $.post( "/ASP/servers/plasma", { action: "plasma", ajax: true, servers: [serverId] })
                .done(function( data ) {

                    // Parse response
                    var result = jQuery.parseJSON(data);
                    if (result.success == false) {
                        $('#jui-global-message')
                            .attr('class', 'alert error')
                            .html(result.message)
                            .append('<span class="close-bt"></span>')
                            .slideDown(500);
                    }
                    else {
                        $("#plasma").html("Yes").css('color', 'green');
                        $("#plasma-server").hide();
                        $("#unplasma-server").show();
                    }
                })
                .fail(function( jqXHR ) {
                    var result = jQuery.parseJSON(jqXHR.responseText);
                    if (result != null)
                    {
                        $('#jui-global-message')
                            .attr('class', 'alert error')
                            .html(result.message)
                            .append('<span class="close-bt"></span>')
                            .slideDown(500);
                    }
                    else
                    {
                        $('#jui-global-message')
                            .attr('class', 'alert error')
                            .html("An Error Occurred. Please check the ASP error log for details.")
                            .append('<span class="close-bt"></span>')
                            .slideDown(500);
                    }
                });

            // Just to be sure, older IE's needs this
            return false;
        });

        // Un-Plasma Click
        $("#unplasma-server").click(function(e) {

            // For all modern browsers, prevent default behavior of the click
            e.preventDefault();

            // Push the request
            $.post( "/ASP/servers/plasma", { action: "unplasma", ajax: true, servers: [serverId] })
                .done(function( data ) {

                    // Parse response
                    var result = jQuery.parseJSON(data);
                    if (result.success == false) {
                        $('#jui-global-message')
                            .attr('class', 'alert error')
                            .html(result.message)
                            .append('<span class="close-bt"></span>')
                            .slideDown(500);
                    }
                    else {
                        $("#plasma").html("No").css('color', 'black');
                        $("#unplasma-server").hide();
                        $("#plasma-server").show();
                    }
                })
                .fail(function( jqXHR ) {
                    var result = jQuery.parseJSON(jqXHR.responseText);
                    if (result != null)
                    {
                        $('#jui-global-message')
                            .attr('class', 'alert error')
                            .html(result.message)
                            .append('<span class="close-bt"></span>')
                            .slideDown(500);
                    }
                    else
                    {
                        $('#jui-global-message')
                            .attr('class', 'alert error')
                            .html("An Error Occurred. Please check the ASP error log for details.")
                            .append('<span class="close-bt"></span>')
                            .slideDown(500);
                    }
                });

            // Just to be sure, older IE's needs this
            return false;
        });

        //////////////////////////////////////////////////////
        // Charts
        /////////////////////////////////////////////////////
        if($.plot) {

            var plot = $.plot($("#mws-line-chart"), [{
                label: "Games Processed by this Server",
                color: "#c75d7b"
            }, {
                label: "Total Games Processed",
                color: "#c5d52b"
            }], {
                tooltip: true,
                tooltipOpts: {
                    content: function(label, xval, yval, flotItem){ // expects to pass these arguments
                        return "%s : %y";
                    },
                    defaultTheme: false,
                    cssClass: 'flotTip'
                },
                series: {
                    lines: {
                        show: true,
                        fill: false
                    },
                    points: {
                        show: true
                    }
                },
                grid: {
                    borderWidth: 0,
                    hoverable: true,
                    clickable: true
                },
                yaxis: {
                    minTickSize: 1,
                    tickDecimals: 0,
                    min:0
                }
            });
        }

        var $result;
        var $loaded = false;

        // Load graph points
        $.getJSON("/ASP/servers/chartData/" + serverId, function(result){
            $result  = result;
            $loaded = true;

            //noinspection JSUnresolvedVariable
            plot.setData([{
                data: $result.week.y.server,
                label: "Games Processed by this Server",
                color: "#c75d7b"
            }, {
                data: $result.week.y.total,
                label: "Total Games Processed",
                color: "#c5d52b"
            }]);

            plot.getAxes().xaxis.options.min = 0;
            //noinspection JSUnresolvedVariable
            plot.getAxes().xaxis.options.max = $result.week.x.total.length - 1;
            //noinspection JSUnresolvedVariable
            plot.getAxes().xaxis.options.ticks = $result.week.x.total;
            plot.setupGrid();
            plot.draw();
        });

        $('#weekRadio').click(function() {
            if ($loaded) {
                //noinspection JSUnresolvedVariable
                plot.setData([{
                    data: $result.week.y.server,
                    label: "Games Processed by this Server",
                    color: "#c75d7b"
                }, {
                    data: $result.week.y.total,
                    label: "Total Games Processed",
                    color: "#c5d52b"
                }]);

                plot.getAxes().xaxis.options.min = 0;
                //noinspection JSUnresolvedVariable
                plot.getAxes().xaxis.options.max = $result.week.x.total.length - 1;
                //noinspection JSUnresolvedVariable
                plot.getAxes().xaxis.options.ticks = $result.week.x.total;
                plot.setupGrid();
                plot.draw();
            }
        });

        $('#monthRadio').click(function() {
            if ($loaded) {
                //noinspection JSUnresolvedVariable
                plot.setData([{
                    data: $result.month.y.server,
                    label: "Games Processed by this Server",
                    color: "#c75d7b"
                }, {
                    data: $result.month.y.total,
                    label: "Total Games Processed",
                    color: "#c5d52b"
                }]);

                plot.getAxes().xaxis.options.min = 0;
                //noinspection JSUnresolvedVariable
                plot.getAxes().xaxis.options.max = $result.month.x.total.length - 1;
                //noinspection JSUnresolvedVariable
                plot.getAxes().xaxis.options.ticks = $result.month.x.total;
                plot.setupGrid();
                plot.draw();
            }
        });

        $('#yearRadio').click(function() {
            if ($loaded) {
                //noinspection JSUnresolvedVariable
                plot.setData([{
                    data: $result.year.y.server,
                    label: "Games Processed by this Server",
                    color: "#c75d7b"
                }, {
                    data: $result.year.y.total,
                    label: "Total Games Processed",
                    color: "#c5d52b"
                }]);

                plot.getAxes().xaxis.options.min = 0;
                //noinspection JSUnresolvedVariable
                plot.getAxes().xaxis.options.max = $result.year.x.total.length - 1;
                //noinspection JSUnresolvedVariable
                plot.getAxes().xaxis.options.ticks = $result.year.x.total;
                plot.setupGrid();
                plot.draw();
            }
        });

        /**
         * Credits to: http://stackoverflow.com/a/5682483/841267
         *
         * @param src
         */
        function checkImage(src) {
            var img = new Image();
            img.onload = function() {
                // code to set the src on success
                $("#server-image").attr("src", src);
            };
            img.onerror = function() {
                // doesn't exist or error loading
            };

            img.src = src; // fires off loading of image
        }

        function queryServer() {

            $("#refresh").data('disabled',true);

            // Fetch online status of server
            $.ajax({
                url: "/ASP/servers/status",
                type: "POST",
                data: { action: "status", ajax: true, serverId: serverId },
                dataType: "json",
                timeout: 10000, // in milliseconds
                success: function(result) {
                    // process data here
                    if (result == null) {
                        $('#jui-global-message')
                            .attr('class', 'alert error')
                            .html("Received empty response from AJAX request.")
                            .append('<span class="close-bt"></span>')
                            .slideDown(500);
                    }
                    // Parse response
                    else if (result.success === true) {

                        // If the server is offline, show that.
                        if (!result.online) {
                            // Fill the rest of the screen
                            $("#status").html("Offline").css('color', 'red');
                            $('#details').html("");
                            $("#jui-global-message").slideUp(200);
                            $("#refresh").data('disabled', false);
                            return;
                        }

                        // Server is online!
                        $('#details').html(result.message);
                        $("#status").html("Online").css('color', 'green');
                        $("#jui-global-message").slideUp(200);

                        // Set image
                        checkImage(result.image);

                        /* Collapsible FIX on new panels */
                        $( '.mws-panel.mws-collapsible' ).each(function(i, element) {
                            var p = $( element ),
                                header = p.find( '.mws-panel-header' );

                            if( header && header.length) {
                                var btn = $('<div class="mws-collapse-button mws-inset"><span></span></div>').appendTo(header);
                                $('span', btn).on( 'click', function(e) {
                                    var p = $( this ).parents( '.mws-panel' );
                                    if( p.hasClass('mws-collapsed') ) {
                                        p.removeClass( 'mws-collapsed' )
                                            .children( '.mws-panel-inner-wrap' ).hide().slideDown( 250 );
                                    } else {
                                        p.children( '.mws-panel-inner-wrap' ).slideUp( 250, function() {
                                            p.addClass( 'mws-collapsed' );
                                        });
                                    }
                                    e.preventDefault();
                                });
                            }

                            if( !p.children( '.mws-panel-inner-wrap' ).length ) {
                                p.children( ':not(.mws-panel-header)' )
                                    .wrapAll( $('<div></div>').addClass( 'mws-panel-inner-wrap' ) );
                            }
                        });

                        // Re-enable button
                       $("#refresh").data('disabled', false);

                        // Data Tables
                        $(".mws-table").DataTable({
                            bPaginate: false,
                            bFilter: false,
                            bInfo: false,
                            order: [[ 2, "desc" ]],
                            columnDefs: [
                                { "orderable": false, "targets": 0 }
                            ]
                        });
                    }
                    else {
                        $('#jui-global-message')
                            .attr('class', 'alert error')
                            .html(result.message)
                            .append('<span class="close-bt"></span>')
                            .slideDown(500);

                        // Fill the rest of the screen
                        $("#status").html("Offline").css('color', 'red');
                    }
                },
                error: function(request, status, err) {
                    if(status == "timeout") {
                        $('#jui-global-message')
                            .attr('class', 'alert error')
                            .html("Request Timed Out.")
                            .append('<span class="close-bt"></span>')
                            .slideDown(500);
                    }
                }
            });
        }
    });
})(jQuery, window, document);
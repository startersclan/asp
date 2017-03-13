;(function ($, window, document, undefined) {

    $(document).ready(function () {

        // Get server ID
        var serverId = parseInt( $("#serverId").html() );

        // Query server online status
        queryServer();

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
            $.post( "/ASP/servers/authorize", { action: "auth", servers: [serverId] })
                .done(function( data ) {

                    // Parse response
                    var result = jQuery.parseJSON(data);
                    if (result.success == false) {
                        $('#jui-global-message')
                            .attr('class', 'alert error')
                            .html(result.message)
                            .slideDown(500)
                            .delay(5000)
                            .fadeOut('slow');
                    }
                    else {
                        $("#auth-server").hide();
                        $("#unauth-server").show();
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
            $.post( "/ASP/servers/authorize", { action: "unauth", servers: [serverId] })
                .done(function( data ) {

                    // Parse response
                    var result = jQuery.parseJSON(data);
                    if (result.success == false) {
                        $('#jui-global-message')
                            .attr('class', 'alert error')
                            .html(result.message)
                            .slideDown(500)
                            .delay(5000)
                            .fadeOut('slow');
                    }
                    else {
                        $("#auth-server").show();
                        $("#unauth-server").hide();
                    }
                });

            // Just to be sure, older IE's needs this
            return false;
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
                data: { action: "status", serverId: serverId },
                dataType: "json",
                timeout: 10000, // in milliseconds
                success: function(result) {
                    // process data here
                    if (result == null) {
                        $('#jui-global-message')
                            .attr('class', 'alert error')
                            .html("Received empty response from AJAX request.")
                            .slideDown(500);
                    }
                    // Parse response
                    else if (result.success == true) {

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
                            .slideDown(500);
                    }
                }
            });
        }
    });
})(jQuery, window, document);
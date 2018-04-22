;(function( $, window, document, undefined ) {

    $(document).ready(function() {

        if( $.plot ) {

            var data = [{
                label: "Snapshots",
                color: "#c75d7b"
            }];

            var plot = $.plot($("#mws-dashboard-chart"), data, {
                series: {
                    lines: {
                        show: true,
                        fill: true
                    },
                    points: {
                        show: true
                    }
                },
                tooltip: true,
                tooltipOpts: {
                    content: function(label, xval, yval, flotItem){ // expects to pass these arguments
                        return "%s : %y";
                    },
                    defaultTheme: false,
                    cssClass: 'flotTip'
                },
                grid: {
                    hoverable: true,
                    borderWidth: 0
                },
                yaxis: {
                    minTickSize: 1,
                    tickDecimals: 0,
                    min:0
                }
            });

            // On Window Resize, redraw chart
            $(window).resize(function() {
                plot.resize();
                plot.setupGrid();
                plot.draw();
            });
        }

        if( $.fn.button ) {
            $("#mws-ui-button-radio").buttonset();
        }

        var $result;
        var $loaded = false;

        // Load graph points
        $.getJSON("/ASP/home/ChartData", function(result){
            $result  = result;
            $loaded = true;

            //noinspection JSUnresolvedVariable
            plot.setData([{
                label: "Games Played",
                color: "#c75d7b",
                data: result.week.y
            }]);

            plot.getAxes().xaxis.options.min = 0;
            //noinspection JSUnresolvedVariable
            plot.getAxes().xaxis.options.max = result.week.x.length - 1;
            //noinspection JSUnresolvedVariable
            plot.getAxes().xaxis.options.ticks = result.week.x;
            plot.setupGrid();
            plot.draw();
        });

        $('#radio1').on('click', function() {
            if ($loaded) {
                //noinspection JSUnresolvedVariable
                plot.setData([{
                    label: "Snapshots",
                    color: "#c75d7b",
                    data: $result.week.y
                }]);

                plot.getAxes().xaxis.options.min = 0;
                //noinspection JSUnresolvedVariable
                plot.getAxes().xaxis.options.max = $result.week.x.length - 1;
                //noinspection JSUnresolvedVariable
                plot.getAxes().xaxis.options.ticks = $result.week.x;
                plot.setupGrid();
                plot.draw();
            }
        });

        $('#radio2').on('click', function() {
            if ($loaded) {
                //noinspection JSUnresolvedVariable
                plot.setData([{
                    label: "Snapshots",
                    color: "#c75d7b",
                    data: $result.month.y
                }]);

                plot.getAxes().xaxis.options.min = 0;
                //noinspection JSUnresolvedVariable
                plot.getAxes().xaxis.options.max = $result.month.x.length - 1;
                //noinspection JSUnresolvedVariable
                plot.getAxes().xaxis.options.ticks = $result.month.x;
                plot.setupGrid();
                plot.draw();
            }
        });

        $('#radio3').on('click', function() {
            if ($loaded) {
                //noinspection JSUnresolvedVariable
                plot.setData([{
                    label: "Snapshots",
                    color: "#c75d7b",
                    data: $result.year.y
                }]);

                plot.getAxes().xaxis.options.min = 0;
                //noinspection JSUnresolvedVariable
                plot.getAxes().xaxis.options.max = $result.year.x.length - 1;
                //noinspection JSUnresolvedVariable
                plot.getAxes().xaxis.options.ticks = $result.year.x;
                plot.setupGrid();
                plot.draw();
            }
        });
    });
}) (jQuery, window, document);
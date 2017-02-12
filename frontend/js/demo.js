;(function( $, window, document, undefined ) {

    $(document).ready(function() {

        if( $.plot ) {
            var PageViews = [];
            for (var i = 0; i <= 7; i++) {
                PageViews.push([i, Math.floor((Math.random() * 10) + 1)]);
            }

            var data = [{
                data: PageViews,
                label: "Snapshots",
                color: "#c75d7b"
            }];

            var plot = $.plot($("#mws-dashboard-chart"), data, {
                series: {
                    lines: {
                        show: true
                    },
                    points: {
                        show: true
                    }
                },
                tooltip: true,
                grid: {
                    hoverable: true,
                    borderWidth: 0,
                },
                xaxis: {
                    min: 0.0,
                    max: 6.0,
                    //mode: null,
                    ticks: [[0.0,"Monday"], [1.0,"Tuesday"], [2.0,"Wednesday"], [3.0,"Thursday"], [4.0,"Friday"], [5.0,"Yesterday"], [6.0,"Today"]]
                },
            });
        }

        if( $.fn.button ) {
            $("#mws-ui-button-radio").buttonset();
        }
    });
}) (jQuery, window, document);
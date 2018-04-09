;(function ($, window, document, undefined) {

    $(document).ready(function () {

        // Data Tables
        var Table = $(".mws-datatable-fn").DataTable({
            pagingType: "full_numbers",
            processing: false,
            serverSide: true,
            order: [[ 1, "desc" ]],
            ajax: {
                url: "/ASP/roundinfo/list",
                type: "POST",
                beforeSend: function() {
                    $('.loading-cell').css('background-image', 'url(/ASP/frontend/images/core/alerts/loading2.gif)');
                },
                complete: function(jqXHR, textStatus) {
                    if (textStatus === "success")
                        $('.loading-cell').css('background-image', 'url(/ASP/frontend/images/core/alerts/tick-circle.png)');
                    else
                        $('.loading-cell').css('background-image', 'url(/ASP/frontend/images/core/alerts/exclamation.png)');
                }
            },
            columns: [
                { "data": "check" },
                { "data": "round_end" },
                { "data": "map" },
                { "data": "server" },
                { "data": "players" },
                { "data": "team1" },
                { "data": "team2" },
                { "data": "winner" },
                { "data": "tickets" },
                { "data": "actions" }
            ],
            columnDefs: [
                { "searchable": false, "orderable": false, "targets": 0 },
                { "orderable": false, "targets": 2 },
                { "searchable": true, "orderable": false, "targets": 3 },
                { "searchable": true, "targets": 4 },
                { "searchable": false, "orderable": false, "targets": 5 },
                { "searchable": false, "orderable": false, "targets": 6 },
                { "searchable": false, "orderable": false, "targets": 7 },
                { "searchable": false, "orderable": false, "targets": 8 },
                { "searchable": false, "orderable": false, "targets": 9 }
            ]
        });

    });
})(jQuery, window, document);
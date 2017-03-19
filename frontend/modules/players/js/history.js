;(function ($, window, document, undefined) {

    $(document).ready(function () {

        var playerId = parseInt($("#playerId").html());

        // Data Tables
        var Table = $(".mws-datatable-fn").DataTable({
            pagingType: "full_numbers",
            processing: false,
            serverSide: true,
            order: [[ 0, "desc" ]],
            ajax: {
                url: "/ASP/players/history",
                type: "POST",
                data: function ( d ) {
                    return $.extend( {}, d, {
                        ajax: true,
                        playerId: playerId
                    });
                }
            },
            columns: [
                { "data": "timestamp" },
                { "data": "map" },
                { "data": "server" },
                { "data": "score" },
                { "data": "kills" },
                { "data": "deaths" },
                { "data": "time" },
                { "data": "team" },
                { "data": "actions" }
            ],
            columnDefs: [
                { "orderable": false, "targets": 1 },
                { "searchable": true, "orderable": false, "targets": 2 },
                { "searchable": true, "targets": 3 },
                { "searchable": false, "targets": 4 },
                { "searchable": false, "targets": 5 },
                { "searchable": false, "orderable": false, "targets": 6 },
                { "searchable": false, "orderable": false, "targets": 7 },
                { "searchable": false, "orderable": false, "targets": 8 }
            ]
        });

    });

})(jQuery, window, document);
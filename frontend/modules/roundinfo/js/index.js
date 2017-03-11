;(function ($, window, document, undefined) {

    $(document).ready(function () {

        // Data Tables
        var Table = $(".mws-datatable-fn").DataTable({
            pagingType: "full_numbers",
            processing: false,
            serverSide: true,
            order: [[ 0, "desc" ]],
            ajax: {
                url: "/ASP/roundinfo/list",
                type: "POST"
            },
            columns: [
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
                { "orderable": false, "targets": 1 },
                { "searchable": true, "orderable": false, "targets": 2 },
                { "searchable": true, "targets": 3 },
                { "searchable": false, "orderable": false, "targets": 4 },
                { "searchable": false, "orderable": false, "targets": 5 },
                { "searchable": false, "orderable": false, "targets": 6 },
                { "searchable": false, "orderable": false, "targets": 7 },
                { "searchable": false, "orderable": false, "targets": 8 }
            ]
        });

    });
})(jQuery, window, document);
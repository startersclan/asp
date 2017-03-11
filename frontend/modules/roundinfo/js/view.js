;(function ($, window, document, undefined) {

    $(document).ready(function () {

        // Data Tables
        $(".mws-table").DataTable({
            bPaginate: false,
            bFilter: false,
            bInfo: false,
            order: [[ 2, "desc" ]],
            columnDefs: [
                { "orderable": false, "targets": 0 },
            ]
        });

    });
})(jQuery, window, document);
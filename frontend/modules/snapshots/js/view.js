;(function ($, window, document) {

    $(document).ready(function () {

        // Data Tables
        $(".custom-sort").DataTable({
            bPaginate: false,
            bFilter: false,
            bInfo: false,
            order: [[ 2, "desc" ]],
            columnDefs: [
                { "orderable": false, "targets": 0 }
            ]
        }).on( 'draw.dt', function () {
            //noinspection JSUnresolvedVariable
            $.fn.tooltip && $('[rel="tooltip"]').tooltip({ "delay": { show: 500, hide: 0 } });
        });

        // Data Tables
        $("#battlespy").DataTable({
            pagingType: "full_numbers",
            bPaginate: true,
            bFilter: true,
            bInfo: true,
            bSort: true,
            order: [[ 0, "desc" ]]
        }).on( 'draw.dt', function () {
            //noinspection JSUnresolvedVariable
            $.fn.tooltip && $('[rel="tooltip"]').tooltip({ "delay": { show: 500, hide: 0 } });
        });

        // Tooltips
        //noinspection JSUnresolvedVariable
        $.fn.tooltip && $('[rel="tooltip"]').tooltip({ "delay": { show: 500, hide: 0 } });

    });
})(jQuery, window, document);
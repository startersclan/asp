;(function( $, window, document, undefined ) {

    $(document).ready(function() {

        if( $.fn.spinner ) {
            $('.mws-spinner').spinner();
        }

        if( $.fn.wizard ) {

            $( '.wzd-default' ).wizard({
                buttonContainerClass: 'mws-button-row'
            });

            if( $.fn.validate ) {
                $wzd_form = $( '.wzd-validate' ).validate({
                    rules: {
                        cfg__db_port: {
                            required: true,
                            min: 1,
                            max: 65535
                        }
                    },
                    onsubmit: false
                });

                $( '.wzd-validate' ).wizard({
                    buttonContainerClass: 'mws-button-row',
                    onStepLeave: function(wizard, step) {
                        return $wzd_form.form();
                    },
                    onBeforeSubmit: function() {
                        return $wzd_form.form();
                    }
                });
            }
        }
    });
}) (jQuery, window, document);
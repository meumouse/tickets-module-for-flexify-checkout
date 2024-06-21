/**
 * Add masks to ticket fields and save to cookies
 * 
 * @since 1.0.0
 * @package MeuMouse.com
 */
jQuery(document).ready( function($) {
    // get ticket fields array from backend
    var get_fields_to_mask = fcw_ticket_fields_params.fields_to_mask || [];

    /**
     * Set input value on cache on browser cookies
     * 
     * @since 1.0.0
     * @param {string} name | Cookie name
     * @param {string} value | Cookie input value
     * @param {int} days | Number days for cache info
     * @package MeuMouse.com
     */
    function set_cookie(name, value, days) {
        var expires = "";

        if (days) {
            var date = new Date();
            date.setTime(date.getTime() + (days*24*60*60*1000));
            expires = "; expires=" + date.toUTCString();
        }

        document.cookie = name + "=" + (value || "") + expires + "; path=/";
    }

    /**
     * Get input value from cookie name
     * 
     * @since 1.0.0
     * @param {string} name | Cookie name
     * @returns Input value or null
     */
    function get_cookie(name) {
        var nameEQ = name + "=";
        var ca = document.cookie.split(';');

        for ( var i = 0; i < ca.length; i++ ) {
            var c = ca[i];

            while ( c.charAt(0) == ' ' ) c = c.substring( 1, c.length );

            if ( c.indexOf(nameEQ) == 0 ) return c.substring( nameEQ.length, c.length );
        }

        return null;
    }

    /**
     * Load input value from cookies
     * 
     * @since 1.0.0
     */
    function load_values_from_cookies() {
        $(get_fields_to_mask).each( function(index, value) {
            var cookie_value = get_cookie(value);

            if (cookie_value) {
                $('#' + value).val(cookie_value);
            }
        });
    }

    // Load values from cookies on page load
    load_values_from_cookies();

    // Add masks and set initial cookie values
    $(get_fields_to_mask).each( function(index, value) {
        if (value.includes('billing_cpf_')) {
            $('#' + value).mask('000.000.000-00');
        } else if (value.includes('billing_phone_')) {
            $('#' + value).mask('(00) 00000-0000');
        }

        // Set initial cookie value
        var initial_value = $('#' + value).val();
        set_cookie(value, initial_value, 7); // Cookie expires in 7 days

        // Update cookie value on change
        $('#' + value).on('input', function() {
            set_cookie(value, $(this).val(), 7);
        });
    });
});
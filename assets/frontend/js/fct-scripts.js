/**
 * Ticket step helpers for Flexify Checkout.
 *
 * @since 1.0.0
 * @version 1.2.0
 * @package MeuMouse.com
 */
( function( window, $ ) {
    'use strict';

    /**
     * Time to live Cookies
     * 
     * @since 1.0.0
     * @version 1.2.0
     * @return {int}
     */
    var COOKIE_TTL_DAYS = 7;

    /**
     * Plugin params
     * 
     * @since 1.0.0
     * @version 1.2.0
     * @return {object}
     */
    var params = window.fcw_ticket_fields_params || {};

    /**
     * Field prefixes array
     * 
     * @since 1.2.0
     * @return {Array}
     */
    var ticketFieldPrefixes = [
        'billing_first_name_',
        'billing_last_name_',
        'billing_cpf_',
        'billing_phone_',
        'billing_email_',
    ];

    /**
     * Fields to mask
     * 
     * @since 1.0.0
     * @version 1.2.0
     * @return {object}
     */
    var fieldIds = [];

    /**
     * Notice selector
     * 
     * @since 1.2.0
     * @return {string}
     */
    var noticeSelector = '.flexify-ticket-errors';

    // Basic cookie storage used to persist values across refreshes.
    var CookieStore = {
        set: function ( name, value, days ) {
            var expires = '';
            var ttl = days || COOKIE_TTL_DAYS;

            if ( ttl ) {
                var date = new Date();
                date.setTime( date.getTime() + ( ttl * 24 * 60 * 60 * 1000 ) );
                expires = '; expires=' + date.toUTCString();
            }

            document.cookie = name + '=' + ( value || '' ) + expires + '; path=/';
        },

        get: function ( name ) {
            var nameEQ = name + '=';
            var cookies = document.cookie.split( ';' );

            for ( var i = 0; i < cookies.length; i++ ) {
                var cookie = cookies[ i ];

                while ( cookie.charAt( 0 ) === ' ' ) {
                    cookie = cookie.substring( 1, cookie.length );
                }

                if ( cookie.indexOf( nameEQ ) === 0 ) {
                    return cookie.substring( nameEQ.length, cookie.length );
                }
            }

            return null;
        }
    };

    // Attach masks to CPF and phone fields to guide user input.
    function applyMaskForField( $field, fieldId ) {
        if ( fieldId.indexOf( 'billing_cpf_' ) !== -1 ) {
            $field.mask( '000.000.000-00' );
        }

        if ( fieldId.indexOf( 'billing_phone_' ) !== -1 ) {
            $field.mask( '(00) 00000-0000' );
        }
    }

    // Locate ticket fields currently rendered in the DOM.
    function findTicketFields() {
        var ids = [];

        ticketFieldPrefixes.forEach( function ( prefix ) {
            $( '[id^="' + prefix + '"]' ).each( function () {
                ids.push( this.id );
            } );
        } );

        return ids;
    }

    // Refresh the list of ticket fields, prioritizing what is rendered on the page.
    function refreshFieldIds() {
        fieldIds = findTicketFields();

        if ( ! fieldIds.length && params.fields_to_mask ) {
            fieldIds = params.fields_to_mask;
        }
    }

    // Restore cached values from cookies for all dynamic ticket fields.
    function loadCachedValues() {
        fieldIds.forEach( function ( fieldId ) {
            var cachedValue = CookieStore.get( fieldId );

            if ( cachedValue ) {
                $( '#' + fieldId ).val( cachedValue );
            }
        } );
    }

    // Cache a specific field on demand.
    function cacheFieldValue( fieldId ) {
        var fieldValue = $( '#' + fieldId ).val();
        CookieStore.set( fieldId, fieldValue, COOKIE_TTL_DAYS );
    }

    /**
     * Add masks and cache handlers to every ticket field.
     * 
     * @since 1.2.0
     */
    function bindCacheHandlers() {
        fieldIds.forEach( function ( fieldId ) {
            var $field = $( '#' + fieldId );

            applyMaskForField( $field, fieldId );
            cacheFieldValue( fieldId );

            $field.off( '.flexifyTicketCache' );
            $field.on( 'input.flexifyTicketCache', function () {
                cacheFieldValue( fieldId );
            });
        });
    }

    // Remove all non-digit characters.
    function normalizeDigits( value ) {
        return ( value || '' ).replace( /\D+/g, '' );
    }

    // Clear visual validation feedback before a new validation cycle.
    function removeValidationFeedback() {
        $( noticeSelector ).remove();

        fieldIds.forEach( function ( fieldId ) {
            $( '#' + fieldId ).removeClass( 'woocommerce-invalid' );
        } );
    }

    // Display WooCommerce styled errors at the top of the checkout form.
    function showValidationMessages( messages ) {
        var $checkoutForm = $( 'form.checkout' );
        var $noticesWrapper = $( '<ul class="woocommerce-error flexify-ticket-errors" />' );

        messages.forEach( function ( message ) {
            $noticesWrapper.append( '<li>' + message + '</li>' );
        } );

        if ( ! $checkoutForm.find( '.woocommerce-notices-wrapper' ).length ) {
            $checkoutForm.prepend( '<div class="woocommerce-notices-wrapper"></div>' );
        }

        $checkoutForm.find( '.woocommerce-notices-wrapper' ).first().prepend( $noticesWrapper );
        window.scrollTo( { top: $checkoutForm.offset().top, behavior: 'smooth' } );
    }

    // Helper used by CPF/phone validators to ensure unique values.
    function validateUniqueValues( values, fieldLabel ) {
        var seen = {};
        var duplicates = [];

        values.forEach( function ( value ) {
            if ( ! value ) {
                return;
            }

            if ( seen[ value ] ) {
                duplicates.push( value );
                return;
            }

            seen[ value ] = true;
        } );

        if ( ! duplicates.length ) {
            return [];
        }

        return [ 'O ' + fieldLabel + ' informado já foi utilizado em outro ingresso. Informe um ' + fieldLabel + ' diferente.' ];
    }

    // Validate ticket fields locally before advancing to the next step.
    function validateTicketFields() {
        if ( ! fieldIds.length ) {
            return { isValid: true, messages: [] };
        }

        removeValidationFeedback();

        var messages = [];
        var cpfList = [];
        var phoneList = [];

        fieldIds.forEach( function ( fieldId ) {
            var $field = $( '#' + fieldId );
            var value = $.trim( $field.val() || '' );

            if ( ! value ) {
                var labelText = $field.closest( '.form-row' ).find( 'label' ).text() || fieldId;
                messages.push( 'Por favor, preencha o campo ' + labelText + '.' );
                $field.addClass( 'woocommerce-invalid' );
            }

            if ( fieldId.indexOf( 'billing_cpf_' ) !== -1 ) {
                cpfList.push( normalizeDigits( value ) );
            }

            if ( fieldId.indexOf( 'billing_phone_' ) !== -1 ) {
                phoneList.push( normalizeDigits( value ) );
            }
        } );

        messages = messages.concat( validateUniqueValues( cpfList, 'CPF' ) ).concat( validateUniqueValues( phoneList, 'telefone' ) );

        return {
            isValid: messages.length === 0,
            messages: messages,
        };
    }

    // Stop step navigation when validation fails and show the errors.
    function handleStepChange( event ) {
        var validation = validateTicketFields();

        if ( validation.isValid ) {
            return true;
        }

        if ( event && typeof event.preventDefault === 'function' ) {
            event.preventDefault();
        }

        if ( event && typeof event.stopImmediatePropagation === 'function' ) {
            event.stopImmediatePropagation();
        }

        showValidationMessages( validation.messages );

        return false;
    }

    function bindStepValidation() {
        $( document.body ).on( 'flexify_checkout_next_step flexify_checkout_before_change_step', handleStepChange );
        $( document.body ).on( 'click', '.flexify-checkout__btn--next, .fc-next-step, .fcw-step-next', handleStepChange );
    }

    function handleCheckoutUpdated() {
        refreshFieldIds();
        removeValidationFeedback();
        loadCachedValues();
        bindCacheHandlers();
    }

    function init() {
        refreshFieldIds();
        loadCachedValues();
        bindCacheHandlers();
        bindStepValidation();

        $(document.body).on( 'updated_checkout', handleCheckoutUpdated );
    }

    $( init );
})( window, jQuery );
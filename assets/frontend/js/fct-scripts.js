/**
 * Ticket step helpers for Flexify Checkout.
 *
 * @since 1.0.0
 * @version 1.2.0
 * @package MeuMouse.com
 */
(function (window, $) {
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
     * Fields to mask / validate
     *
     * @since 1.0.0
     * @version 1.2.0
     * @return {Array}
     */
    var fieldIds = [];

    /**
     * Notice selector
     *
     * @since 1.2.0
     * @return {string}
     */
    var noticeSelector = '.flexify-ticket-errors';

    /**
     * Inline error class used by this module
     *
     * @since 1.2.0
     * @return {string}
     */
    var inlineErrorClass = 'flexify-tkt-inline-error';

    /**
     * Basic cookie store
     *
     * @since 1.0.0
     * @version 1.2.0
     */
    var CookieStore = {
        set: function (name, value, days) {
            var expires = '';
            var ttl = days || COOKIE_TTL_DAYS;

            if (ttl) {
                var date = new Date();
                date.setTime(date.getTime() + ttl * 24 * 60 * 60 * 1000);
                expires = '; expires=' + date.toUTCString();
            }

            document.cookie = name + '=' + (value || '') + expires + '; path=/';
        },

        get: function (name) {
            var nameEQ = name + '=';
            var cookies = document.cookie.split(';');

            for (var i = 0; i < cookies.length; i++) {
                var cookie = cookies[i];

                while (cookie.charAt(0) === ' ') {
                    cookie = cookie.substring(1, cookie.length);
                }

                if (cookie.indexOf(nameEQ) === 0) {
                    return cookie.substring(nameEQ.length, cookie.length);
                }
            }

            return null;
        }
    };

    /**
     * Attach masks to CPF and phone fields
     *
     * @since 1.0.0
     * @version 1.2.0
     */
    function applyMaskForField($field, fieldId) {
        if (!$field.is('input, textarea')) {
            return;
        }

        if (fieldId.indexOf('billing_cpf_') !== -1 && $.fn.mask) {
            $field.mask('000.000.000-00');
        }

        if (fieldId.indexOf('billing_phone_') !== -1 && $.fn.mask) {
            // Skip phone mask when using intl-tel-input
            if ($field.closest('.flexify-intl-phone').length) {
                return;
            }

            $field.mask('(00) 00000-0000');
        }
    }

    /**
     * Find ticket fields currently in DOM
     *
     * @since 1.2.0
     * @return {Array}
     */
    function findTicketFields() {
        var ids = [];

        ticketFieldPrefixes.forEach(function (prefix) {
            $('input[id^="' + prefix + '"]').each(function () {
                var id = this.id;

                // For phone fields, only accept billing_phone_N (N = dígitos, sem sufixo _full)
                if (prefix === 'billing_phone_') {
                    if (!/^billing_phone_\d+$/.test(id)) {
                        return;
                    }
                }

                ids.push(id);
            });
        });

        return ids;
    }

    /**
     * Refresh list of ticket fields
     *
     * @since 1.2.0
     */
    function refreshFieldIds() {
        fieldIds = findTicketFields();

        if (!fieldIds.length && params.fields_to_mask) {
            fieldIds = params.fields_to_mask;
        }
    }

    /**
     * Load cached values from cookies
     *
     * @since 1.0.0
     * @version 1.2.0
     */
    function loadCachedValues() {
        fieldIds.forEach(function (fieldId) {
            var cachedValue = CookieStore.get(fieldId);

            if (cachedValue) {
                $('#' + fieldId).val(cachedValue);
            }
        });
    }

    /**
     * Cache a single field value
     *
     * @since 1.0.0
     * @version 1.2.0
     */
    function cacheFieldValue(fieldId) {
        var fieldValue = $('#' + fieldId).val();
        CookieStore.set(fieldId, fieldValue, COOKIE_TTL_DAYS);
    }

    /**
     * Add masks and cache handlers for ticket fields
     *
     * @since 1.2.0
     */
    function bindCacheHandlers() {
        fieldIds.forEach(function (fieldId) {
            var $field = $('#' + fieldId);

            applyMaskForField($field, fieldId);
            cacheFieldValue(fieldId);

            $field.off('.flexifyTicketCache');
            $field.on('input.flexifyTicketCache', function () {
                cacheFieldValue(fieldId);

                // Any change re-enables next step button
                $('button.flexify-button[data-step-next]').prop('disabled', false);
            });
        });
    }

    /**
     * Normalize to digits only
     *
     * @since 1.2.0
     */
    function normalizeDigits(value) {
        return (value || '').replace(/\D+/g, '');
    }

    /**
     * Clear validation feedback
     *
     * @since 1.2.0
     * @version 1.2.0
     */
    function removeValidationFeedback() {
        // Remove old global notices from this module
        $('.flexify-checkout-notice.error').remove();
        $(noticeSelector).remove();

        fieldIds.forEach(function (fieldId) {
            var $field = $('#' + fieldId);
            var $row = $field.closest('.form-row');

            $field
                .removeClass('woocommerce-invalid')
                .removeAttr('aria-invalid');

            $row
                .removeClass('woocommerce-invalid')
                .removeClass('woocommerce-invalid-required-field');

            // Remove only our inline handler; keep base Woo span.error if needed
            $row.find('.' + inlineErrorClass).remove();
        });
    }

    /**
     * Show global WooCommerce-style messages
     * Using the same structure as error.php template.
     *
     * @since 1.2.0
     */
    function showValidationMessages(messages) {
        if (!messages || !messages.length) {
            return;
        }

        // Remove duplicates
        var uniqueMessages = [];
        messages.forEach(function (msg) {
            if (uniqueMessages.indexOf(msg) === -1) {
                uniqueMessages.push(msg);
            }
        });

        if (!uniqueMessages.length) {
            return;
        }

        var $checkoutForm = $('form.checkout');
        var $wrapper = $checkoutForm.find('.woocommerce-notices-wrapper');

        if (!$wrapper.length) {
            $wrapper = $('<div class="woocommerce-notices-wrapper"></div>');
            $checkoutForm.prepend($wrapper);
        }

        // Remove previous notices from this module
        $wrapper.find('.flexify-checkout-notice.error').remove();

        uniqueMessages.forEach(function (message) {
            var $notice = $('<div/>', {
                'class': 'woocommerce-error flexify-checkout-notice error',
                'role': 'alert'
            });

            // Text content only (no HTML to escape)
            $notice.append(document.createTextNode(message));

            var $closeBtn = $('<button/>', {
                'class': 'close-notice btn-close btn-close-white',
                'type': 'button'
            });

            $notice.append($closeBtn);
            $wrapper.prepend($notice);
        });

        window.scrollTo({
            top: $checkoutForm.offset().top,
            behavior: 'smooth'
        });
    }

    /**
     * Show inline error messages per field, without duplicates
     *
     * @since 1.2.0
     * @param {Object} fieldMessages | { fieldId: [messages] }
     */
    function showInlineFieldMessages(fieldMessages) {
        if (!fieldMessages) {
            return;
        }

        Object.keys(fieldMessages).forEach(function (fieldId) {
            var msgs = fieldMessages[fieldId];

            if (!msgs || !msgs.length) {
                return;
            }

            var $field = $('#' + fieldId);
            var $row = $field.closest('.form-row');
            var message = msgs[0]; // First message is enough for UI

            // Find existing Woo error span, if any
            var $existingError = $row.find('span.error').first();

            if ($existingError.length) {
                $existingError
                    .text(message)
                    .addClass(inlineErrorClass);

                // Remove any extra error spans to avoid duplicates
                $row.find('span.error').not($existingError).remove();
            } else {
                // Create a single new error span
                var $error = $('<span/>', {
                    'class': 'error ' + inlineErrorClass,
                    text: message
                });

                $row.append($error);
            }

            // Mark as invalid in WooCommerce/Flexify style
            $field
                .addClass('woocommerce-invalid')
                .attr('aria-invalid', 'true');

            $row
                .addClass('woocommerce-invalid')
                .addClass('woocommerce-invalid-required-field');
        });
    }

    /**
     * Validate unique values and build both global and inline messages
     *
     * @since 1.2.0
     * @param {Array} entries        | [{ id: 'billing_cpf_1', value: '123' }, ...]
     * @param {String} fieldLabel    | "CPF", "telefone", "e-mail"
     * @param {Array} globalMessages | Will be mutated
     * @param {Object} fieldMessages | { fieldId: [messages] } will be mutated
     */
    function validateUniqueValues(entries, fieldLabel, globalMessages, fieldMessages) {
        if (!entries || !entries.length) {
            return;
        }

        var grouped = {};
        var hasDuplicates = false;

        entries.forEach(function (entry) {
            if (!entry.value) {
                return;
            }

            if (!grouped[entry.value]) {
                grouped[entry.value] = [];
            }

            grouped[entry.value].push(entry.id);
        });

        Object.keys(grouped).forEach(function (value) {
            var ids = grouped[value];

            if (ids.length > 1) {
                hasDuplicates = true;

                ids.forEach(function (fieldId) {
                    var msg =
                        'O ' +
                        fieldLabel +
                        ' informado para este ingresso já foi utilizado em outro ingresso. Informe um ' +
                        fieldLabel +
                        ' diferente.';

                    if (!fieldMessages[fieldId]) {
                        fieldMessages[fieldId] = [];
                    }

                    fieldMessages[fieldId].push(msg);

                    var $field = $('#' + fieldId);
                    var $row = $field.closest('.form-row');

                    $field
                        .addClass('woocommerce-invalid')
                        .attr('aria-invalid', 'true');

                    $row
                        .addClass('woocommerce-invalid')
                        .addClass('woocommerce-invalid-required-field');
                });
            }
        });

        if (hasDuplicates) {
            globalMessages.push(
                'Existem ingressos com ' +
                    fieldLabel +
                    ' repetido. Cada ingresso deve possuir um ' +
                    fieldLabel +
                    ' único.'
            );
        }
    }

    /**
     * Validate ticket fields before moving to next step
     *
     * @since 1.2.0
     * @version 1.2.0
     * @return {Object}
     */
    function validateTicketFields() {
        // Refresh fields (in case fragments were updated)
        refreshFieldIds();
        bindCacheHandlers();

        if (!fieldIds.length) {
            return { isValid: true, messages: [], fieldMessages: {} };
        }

        removeValidationFeedback();

        var messages = [];
        var fieldMessages = {};

        var cpfEntries = [];
        var phoneEntries = [];
        var emailEntries = [];

        fieldIds.forEach(function (fieldId) {
            var $field = $('#' + fieldId);
            var value = $.trim($field.val() || '');
            var $row = $field.closest('.form-row');

            if (!value) {
                var labelText = $.trim($row.find('label').first().text()) || fieldId;
                labelText = labelText.replace(/\*$/, '').trim();

                var msg = labelText + ' é um campo obrigatório.';

                messages.push(msg);

                if (!fieldMessages[fieldId]) {
                    fieldMessages[fieldId] = [];
                }

                if (fieldMessages[fieldId].indexOf(msg) === -1) {
                    fieldMessages[fieldId].push(msg);
                }

                $field
                    .addClass('woocommerce-invalid')
                    .attr('aria-invalid', 'true');

                $row
                    .addClass('woocommerce-invalid')
                    .addClass('woocommerce-invalid-required-field');
            }

            // Lists for duplicate checking
            if (fieldId.indexOf('billing_cpf_') !== -1 && value) {
                cpfEntries.push({
                    id: fieldId,
                    value: normalizeDigits(value)
                });
            }

            if (fieldId.indexOf('billing_phone_') !== -1 && value) {
                phoneEntries.push({
                    id: fieldId,
                    value: normalizeDigits(value)
                });
            }

            if (fieldId.indexOf('billing_email_') !== -1 && value) {
                emailEntries.push({
                    id: fieldId,
                    value: value.toLowerCase()
                });
            }
        });

        // Duplicate validation: CPF, phone, email
        validateUniqueValues(cpfEntries, 'CPF', messages, fieldMessages);
        validateUniqueValues(phoneEntries, 'telefone', messages, fieldMessages);
        validateUniqueValues(emailEntries, 'e-mail', messages, fieldMessages);

        return {
            isValid: messages.length === 0,
            messages: messages,
            fieldMessages: fieldMessages
        };
    }

    /**
     * Handle step change and block step when invalid
     *
     * @since 1.2.0
     * @version 1.2.0
     */
    function handleStepChange(nativeEvent) {
        var validation = validateTicketFields();

        if (validation.isValid) {
            return true;
        }

        if (nativeEvent) {
            nativeEvent.preventDefault();
            nativeEvent.stopPropagation();
        }

        // Disable next button until user edits something
        var btn = nativeEvent && nativeEvent.target
            ? nativeEvent.target.closest('button.flexify-button[data-step-next]')
            : null;

        if (btn) {
            btn.setAttribute('disabled', 'disabled');
        }

        // Inline + global messages
        showInlineFieldMessages(validation.fieldMessages);
        showValidationMessages(validation.messages);

        return false;
    }

    /**
     * Bind validation to "Continuar para pagamento" button
     * using capture phase so we run before Flexify handlers.
     *
     * @since 1.2.0
     */
    function bindStepValidation() {
        document.addEventListener(
            'click',
            function (e) {
                var btn = e.target.closest('button.flexify-button[data-step-next][data-step-show="3"]');

                if (!btn) {
                    return;
                }

                handleStepChange(e);
            },
            true // capture
        );
    }

        /**
     * Ensure hidden intl phone field billing_phone_{i}_full exists
     *
     * @since 1.2.0
     * @param {jQuery} $field Phone input
     * @return {jQuery|null}
     */
    function ensureIntlPhoneFullField($field) {
        if (!$field || !$field.length) {
            return null;
        }

        var id = $field.attr('id') || '';
        var match = id.match(/^billing_phone_(\d+)$/);

        if (!match) {
            return null;
        }

        var index = match[1];
        var fullId = 'billing_phone_' + index + '_full';
        var $row = $field.closest('.form-row');
        var $hidden = $row.find('#' + fullId);

        if (!$hidden.length) {
            $hidden = $('<input/>', {
                type: 'hidden',
                id: fullId,
                name: fullId
            });

            // Pode ser no final da .form-row mesmo
            $row.append($hidden);
        }

        return $hidden;
    }

    /**
     * Update hidden intl phone field with E.164-like value
     *
     * @since 1.2.0
     * @param {jQuery} $field Phone input
     */
    function updateIntlPhoneFull($field) {
        if (!$field || !$field.length) {
            return;
        }

        var $hidden = ensureIntlPhoneFullField($field);

        if (!$hidden || !$hidden.length) {
            return;
        }

        // Apenas dígitos do input
        var raw = normalizeDigits($field.val());

        // Tentar pegar o DDI pelo wrapper .iti
        var dialCode = '';
        var $iti = $field.closest('.iti');

        if ($iti.length) {
            var $dial = $iti.find('.iti__selected-dial-code').first();

            if ($dial.length) {
                dialCode = $.trim($dial.text().replace(/\s+/g, '')); // ex: '+55'
            }
        }

        // Se intl-tel-input estiver disponível, usar a API (melhor)
        if (!dialCode && window.intlTelInputGlobals && typeof window.intlTelInputGlobals.getInstance === 'function') {
            var instance = window.intlTelInputGlobals.getInstance($field[0]);

            if (instance && typeof instance.getNumber === 'function') {
                var number = instance.getNumber(); // já vem em E.164

                $hidden.val(number || '');
                return;
            }
        }

        if (dialCode && raw) {
            // dialCode já começa com '+'
            $hidden.val(dialCode + raw);
        } else {
            $hidden.val('');
        }
    }

    /**
     * Bind handlers to keep billing_phone_{i}_full synced
     *
     * @since 1.2.0
     */
    function bindIntlPhoneFullHandlers() {
        // Delegated events (funciona mesmo após fragmentos de checkout)
        $(document)
            .off('.flexifyTicketIntl')
            .on(
                'input.flexifyTicketIntl change.flexifyTicketIntl countrychange.flexifyTicketIntl',
                '.flexify-intl-phone input[id^="billing_phone_"]',
                function () {
                    updateIntlPhoneFull($(this));
                }
            );

        // Sync inicial para campos já presentes
        $('.flexify-intl-phone input[id^="billing_phone_"]').each(function () {
            updateIntlPhoneFull($(this));
        });
    }

    /**
     * Init ticket helper
     *
     * @since 1.0.0
     * @version 1.2.0
     */
    function init() {
        refreshFieldIds();
        loadCachedValues();
        bindCacheHandlers();
        bindStepValidation();
        bindIntlPhoneFullHandlers();
    }

    $(init);
})(window, jQuery);
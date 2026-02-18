(function ($) {
    'use strict';

    function cfg() {
        return window.WooOffersAdmin || {};
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/\"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function rowExists(productId) {
        return $('#woo-offers-rules-body tr[data-product-id="' + productId + '"]').length > 0;
    }

    function parseNumber(value) {
        if (value === undefined || value === null || value === '') {
            return null;
        }

        var raw = String(value).trim();
        if (!raw) {
            return null;
        }

        raw = raw.replace(/[^\d.,-]/g, '');
        if (!raw) {
            return null;
        }

        var lastDot = raw.lastIndexOf('.');
        var lastComma = raw.lastIndexOf(',');

        if (lastDot !== -1 && lastComma !== -1) {
            if (lastDot > lastComma) {
                raw = raw.replace(/,/g, '');
            } else {
                raw = raw.replace(/\./g, '').replace(',', '.');
            }
        } else if (lastDot !== -1) {
            var dotDecimals = raw.length - lastDot - 1;
            if (dotDecimals > 2) {
                raw = raw.replace(/\./g, '');
            }
        } else if (lastComma !== -1) {
            var commaDecimals = raw.length - lastComma - 1;
            if (commaDecimals > 2) {
                raw = raw.replace(/,/g, '');
            } else {
                raw = raw.replace(',', '.');
            }
        }

        var n = Number(raw);

        return Number.isFinite(n) ? n : null;
    }

    function setRowStatus($row, state, text) {
        var $status = $row.find('.woo-offers-status');

        $status
            .removeClass('woo-offers-status--applied woo-offers-status--invalid woo-offers-status--unknown')
            .addClass('woo-offers-status--' + state)
            .text(text);

        $row.toggleClass('woo-offers-row-invalid', state === 'invalid' || state === 'unknown');
    }

    function validateRow($row) {
        var labels = cfg();
        var regularPrice = parseNumber($row.find('input[name="regular_prices[]"]').val());
        var offerPrice = parseNumber($row.find('input[name="offer_prices[]"]').val());

        if (regularPrice === null || regularPrice <= 0) {
            setRowStatus($row, 'unknown', labels.unknownLabel || 'No validable (producto sin precio normal)');
            return false;
        }

        if (offerPrice === null || offerPrice <= 0 || offerPrice >= regularPrice) {
            setRowStatus($row, 'invalid', labels.invalidLabel || 'No aplicada (la oferta debe ser menor al precio normal)');
            return false;
        }

        setRowStatus($row, 'applied', labels.appliedLabel || 'Aplicada');
        return true;
    }

    function validateAllRows() {
        var allValid = true;

        $('#woo-offers-rules-body tr').each(function () {
            var rowValid = validateRow($(this));
            if (!rowValid) {
                allValid = false;
            }
        });

        return allValid;
    }

    function buildRow(item) {
        var labels = cfg();
        var safeId = escapeHtml(item.product_id);
        var safeName = escapeHtml(item.name || '');
        var safeSku = escapeHtml(item.sku || '');
        var safeCurrentPrice = escapeHtml(item.current_price || (labels.naLabel || ' - '));
        var safeCurrentRaw = item.current_price_raw !== null && item.current_price_raw !== undefined
            ? escapeHtml(item.current_price_raw)
            : '';
        var safeRegularInput = item.regular_price_input !== null && item.regular_price_input !== undefined && item.regular_price_input !== ''
            ? escapeHtml(item.regular_price_input)
            : item.regular_price_raw !== null && item.regular_price_raw !== undefined
                ? escapeHtml(item.regular_price_raw)
                : '';

        return '' +
            '<tr data-product-id="' + safeId + '" data-current-price="' + safeCurrentRaw + '">' +
                '<td class="woo-col-product">' + safeName +
                    '<input type="hidden" name="product_ids[]" value="' + safeId + '" />' +
                '</td>' +
                '<td class="woo-col-sku">' + safeSku + '</td>' +
                '<td class="woo-col-current-price">' + safeCurrentPrice + '</td>' +
                '<td><input type="text" inputmode="decimal" class="small-text woo-offers-price-input" name="regular_prices[]" value="' + safeRegularInput + '" required /></td>' +
                '<td><input type="text" inputmode="decimal" class="small-text woo-offers-price-input" name="offer_prices[]" required /></td>' +
                '<td class="woo-offers-status woo-offers-status--unknown">' + escapeHtml(labels.pendingLabel || 'Pendiente de guardar') + '</td>' +
                '<td><button type="button" class="button-link-delete woo-offers-remove-row">' + escapeHtml(labels.removeLabel || 'Quitar') + '</button></td>' +
            '</tr>';
    }

    function showValidationMessage(message) {
        var $message = $('#woo-offers-validation-message');
        $message.find('p').text(message);
        $message.show();
    }

    function hideValidationMessage() {
        $('#woo-offers-validation-message').hide();
    }

    function requestProductsContext(ids) {
        var labels = cfg();

        return $.ajax({
            url: labels.ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'woo_offers_products_context',
                nonce: labels.nonce,
                ids: ids
            }
        });
    }

    function addProductsToTable(selectedOptions) {
        var ids = [];

        selectedOptions.each(function () {
            var productId = $(this).val();
            if (productId && !rowExists(productId)) {
                ids.push(productId);
            }
        });

        if (!ids.length) {
            return;
        }

        requestProductsContext(ids).done(function (response) {
            if (!response || !response.success || !response.data || !Array.isArray(response.data.items)) {
                return;
            }

            response.data.items.forEach(function (item) {
                if (rowExists(item.product_id)) {
                    return;
                }

                var $row = $(buildRow(item));
                $('#woo-offers-rules-body').append($row);
                validateRow($row);
            });
        });
    }

    $(function () {
        var $search = $('#woo-offers-product-search');

        if ($search.length) {
            $search.filter(':not(.enhanced)').each(function () {
                $(this).wc_product_search();
            });
        }

        validateAllRows();

        $('#woo-offers-add-products').on('click', function () {
            addProductsToTable($search.find('option:selected'));
            $search.val(null).trigger('change');
        });

        $(document).on('click', '.woo-offers-remove-row', function () {
            $(this).closest('tr').remove();
            hideValidationMessage();
        });

        $(document).on('input change', 'input[name="offer_prices[]"], input[name="regular_prices[]"]', function () {
            validateRow($(this).closest('tr'));
            hideValidationMessage();
        });

        $('#woo-offers-admin-form').on('submit', function (event) {
            var valid = validateAllRows();

            if (!valid) {
                event.preventDefault();
                showValidationMessage(cfg().invalidFormMessage || 'Hay reglas invalidas.');
            }
        });
    });
})(jQuery);

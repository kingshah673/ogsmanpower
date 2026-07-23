/**
 * Unified Select2 for candidate/company settings — AJAX suggestions + consistent styling.
 */
(function (window, $) {
    'use strict';

    function bootWhenReady(attempt) {
        attempt = attempt || 0;
        if (!$ || !$.fn || !$.fn.select2) {
            if (attempt < 40) {
                setTimeout(function () {
                    bootWhenReady(attempt + 1);
                }, 50);
            }
            return;
        }
        boot();
    }

    function boot() {
        var lookupUrl = window.cwSettingsLookupUrl || '/candidate/settings/lookup';

        function optionValue($el, item) {
            return $el.data('cw-value') === 'text' ? item.text : item.id;
        }

        function buildConfig($el) {
            var lookup = $el.data('cw-lookup');
            var multiple = $el.prop('multiple') || $el.data('cw-multiple');
            var allowTags = String($el.data('cw-tags')) === '1' || String($el.data('cw-tags')) === 'true';
            var placeholder = $el.data('placeholder') || (multiple ? 'Search and select…' : 'Select one…');

            var config = {
                theme: 'bootstrap4',
                width: '100%',
                placeholder: placeholder,
                allowClear: !multiple,
                closeOnSelect: !multiple,
                dropdownParent: $(document.body),
            };

            if (lookup) {
                var preselected = [];
                $el.find('option').each(function () {
                    var val = $(this).attr('value');
                    if (val !== undefined && val !== '' && $(this).prop('selected')) {
                        preselected.push({ id: val, text: $(this).text() });
                    }
                });
                if (preselected.length) {
                    config.data = preselected;
                }

                config.minimumInputLength = 0;
                config.ajax = {
                    url: lookupUrl + '/' + lookup,
                    dataType: 'json',
                    delay: 250,
                    cache: true,
                    data: function (params) {
                        return {
                            q: params.term || '',
                            page: params.page || 1,
                        };
                    },
                    processResults: function (data, params) {
                        params.page = params.page || 1;
                        var results = (data.results || []).map(function (item) {
                            return {
                                id: optionValue($el, item),
                                text: item.text,
                            };
                        });
                        return {
                            results: results,
                            pagination: {
                                more: data.pagination && data.pagination.more,
                            },
                        };
                    },
                };
            }

            if (multiple) {
                config.multiple = true;
            }

            if (allowTags) {
                config.tags = true;
                config.tokenSeparators = [','];
                config.createTag = function (params) {
                    var term = $.trim(params.term);
                    if (term === '') {
                        return null;
                    }
                    return { id: term, text: term };
                };
            }

            return config;
        }

        function initElement($el) {
            if (!$el.length || $el.hasClass('select2-hidden-accessible')) {
                return;
            }
            if ($el.hasClass('cw-static-select')) {
                $el.select2({
                    theme: 'bootstrap4',
                    width: '100%',
                    minimumResultsForSearch: $el.find('option').length > 8 ? 0 : Infinity,
                    dropdownParent: $(document.body),
                });
                return;
            }
            if (!$el.hasClass('cw-ms-select') && !$el.data('cw-lookup')) {
                return;
            }
            $el.select2(buildConfig($el));
        }

        function initAll(root) {
            if (root) {
                $(root).find('.cw-ms-select, .cw-static-select, select[data-cw-lookup]').each(function () {
                    initElement($(this));
                });
                return;
            }
            $('select.cw-ms-select, select.cw-static-select, select[data-cw-lookup]').each(function () {
                initElement($(this));
            });
        }

        function refreshInForm(formId) {
            var $form = $('#' + formId);
            if (!$form.length) {
                return;
            }
            $form.find('select.cw-ms-select, select.cw-static-select, select[data-cw-lookup]').each(function () {
                var $el = $(this);
                if ($el.hasClass('select2-hidden-accessible')) {
                    $el.select2('destroy');
                }
                initElement($el);
            });
        }

        window.cwInitSettingsSelects = initAll;
        window.cwRefreshSettingsSelects = refreshInForm;

        function ensureNativeOption($el, val) {
            if (val === undefined || val === null || val === '') {
                return;
            }
            var strVal = String(val);
            if ($el.find('option').filter(function () { return $(this).val() === strVal; }).length) {
                $el.val(strVal);
                return;
            }
            var text = strVal;
            var data = $el.select2('data');
            if (Array.isArray(data) && data[0] && data[0].text) {
                text = data[0].text;
            } else if (data && data.text) {
                text = data.text;
            } else {
                var selected = $el.find('option:selected').text();
                if (selected) text = selected;
            }
            $el.append(new Option(text, strVal, true, true));
            $el.val(strVal);
        }

        /**
         * Copy Select2 selections into the native <select> so POST includes all values.
         */
        window.cwSyncSelect2BeforeSubmit = function (form) {
            var $form = form ? $(form) : $(document);
            $form.find('select.select2-hidden-accessible').each(function () {
                var $el = $(this);
                var isMultiple = $el.prop('multiple');
                var isLocation = $el.is('#basic_country, #basic_state, #basic_city');

                if (!isMultiple) {
                    ensureNativeOption($el, $el.val());
                    if (isLocation) {
                        $el.prop('disabled', false);
                    }
                    return;
                }

                var selected = $el.select2('data') || [];
                if (!selected.length) {
                    return;
                }
                $el.empty();
                selected.forEach(function (item) {
                    if (item.id === undefined || item.id === null || item.id === '') {
                        return;
                    }
                    var opt = new Option(item.text, item.id, true, true);
                    $el.append(opt);
                });
                $el.trigger('change');
            });
        };

        $(function () {
            initAll();
            if (typeof window.cwBootBasicLocation === 'function') {
                window.cwBootBasicLocation();
            }
        });
    }

    bootWhenReady();
})(window, window.jQuery);

/**
 * Candidate settings — AJAX section saves, auto-save, completion bar updates.
 */
(function (window, $) {
    'use strict';

    var config = window.cwSettingsSaveConfig || {};
    var saveUrl = config.saveUrl || '/candidate/settings/update';
    var settingsPageUrl = config.settingsUrl || window.location.pathname;
    var csrfToken = function () {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    };

    function toast(type, message) {
        if (window.toastr && typeof window.toastr[type] === 'function') {
            window.toastr[type](message);
        }
    }

    function applyCsrfToken(token) {
        if (!token) return;
        var meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) meta.setAttribute('content', token);
        document.querySelectorAll('input[name="_token"]').forEach(function (input) {
            input.value = token;
        });
    }

    function refreshCsrfToken() {
        var url = config.csrfRefreshUrl || '/refresh-csrf';
        return fetch(url, {
            credentials: 'same-origin',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(function (res) { return res.ok ? res.json() : null; })
            .then(function (data) {
                if (data && data.token) applyCsrfToken(data.token);
            })
            .catch(function () {});
    }

    function updateCompletionBar(data) {
        if (!data) return;
        var pct = data.completionPercentage;
        if (pct === undefined || pct === null) return;

        document.querySelectorAll('.profile_section .progress-bar, .cw-profile-completion-hints ~ .progress-bar').forEach(function (bar) {
            bar.style.width = Math.min(100, pct) + '%';
            bar.setAttribute('aria-valuenow', pct);
        });
        document.querySelectorAll('.profile_section .progress-label span:last-child').forEach(function (el) {
            el.textContent = Math.round(pct) + '%';
        });
        document.querySelectorAll('.cw-profile-completion-hints__pct').forEach(function (el) {
            el.textContent = '(' + Math.round(pct) + '%)';
        });

        var missing = data.profileCompletionMissing || [];
        document.querySelectorAll('.cw-profile-completion-hints').forEach(function (block) {
            if (pct >= 100 || !missing.length) {
                block.style.display = 'none';
                return;
            }
            block.style.display = '';
            var list = block.querySelector('.cw-profile-completion-hints__list');
            if (!list) return;
            list.innerHTML = missing.map(function (item) {
                return '<li><a href="' + settingsPageUrl + '#' + item.anchor + '" class="cw-profile-completion-hints__link">' +
                    '<strong>' + item.label + '</strong><span>' + item.hint + '</span></a></li>';
            }).join('');
        });
    }

    function prepareBasicLocationPayload(form) {
        if (!form || form.id !== 'basicForm') return;
        ['basic_country', 'basic_state', 'basic_city'].forEach(function (id) {
            var el = document.getElementById(id);
            if (!el) return;
            el.disabled = false;
            var $el = $(el);
            var val = $el.val();
            if (!val && $el.hasClass('select2-hidden-accessible')) {
                var data = $el.select2('data');
                if (Array.isArray(data) && data[0] && data[0].id) {
                    val = data[0].id;
                } else if (data && data.id) {
                    val = data.id;
                }
            }
            if (!val) return;
            if (!$el.find('option').filter(function () { return $(this).val() === String(val); }).length) {
                var text = $el.find('option:selected').text() || val;
                $el.append(new Option(text, val, true, true));
            }
            $el.val(val);
        });
    }

    function prepareJobFormPayload(form) {
        if (!form || form.id !== 'jobForm') return;
        var $form = $(form);
        var jobsPayload = document.getElementById('jobs_payload');
        var industriesPayload = document.getElementById('industries_payload');
        var $jobs = $form.find('select[name="jobs[]"]');
        var $industries = $form.find('select[name="industries[]"]');
        if (jobsPayload) jobsPayload.value = JSON.stringify($jobs.val() || []);
        if (industriesPayload) industriesPayload.value = JSON.stringify($industries.val() || []);
    }

    function applySectionPreview(data) {
        if (!data || !data.preview) return;
        var p = data.preview;

        if (p.full_name) {
            var nameEl = document.getElementById('cwPreviewFullName');
            if (nameEl) nameEl.textContent = p.full_name;
        }
        if (p.email) {
            var emailEl = document.getElementById('cwPreviewEmail');
            if (emailEl) emailEl.textContent = p.email;
        }
        if (p.whatsapp !== undefined) {
            var waEl = document.getElementById('cwPreviewWhatsapp');
            if (waEl) waEl.textContent = p.whatsapp || '';
        }
        if (p.location) {
            var locEl = document.getElementById('cwPreviewLocation');
            if (locEl) {
                locEl.classList.remove('d-none');
                locEl.innerHTML = '<i class="fas fa-map-marker-alt mr-1"></i> ' + p.location;
            }
        }
        if (p.bio !== undefined) {
            var summaryEl = document.getElementById('summaryPreview');
            if (summaryEl) summaryEl.innerHTML = '<div>' + String(p.bio).replace(/\n/g, '<br>') + '</div>';
        }
        if (p.skills) {
            var skillEl = document.getElementById('skillPreview');
            if (skillEl) {
                skillEl.innerHTML = p.skills.map(function (s) {
                    return '<span class="cw-tag">' + s + '</span>';
                }).join('');
            }
        }
        if (p.languages) {
            var langEl = document.getElementById('languagePreview');
            if (langEl) {
                langEl.innerHTML = p.languages.map(function (l) {
                    return '<span class="cw-tag">' + l + '</span>';
                }).join('');
            }
        }
        if (p.phone !== undefined || p.email !== undefined) {
            var accountPrev = document.getElementById('accountPreview');
            if (accountPrev) {
                accountPrev.innerHTML =
                    '<h6>' + (config.contactTitle || 'Your Contact Information') + '</h6>' +
                    '<p><strong>Phone:</strong> ' + (p.phone || '—') + '</p>' +
                    '<p><strong>Secondary Phone:</strong> ' + (p.secondary_phone || '—') + '</p>' +
                    '<p><strong>Whatsapp Number:</strong> ' + (p.whatsapp || '—') + '</p>' +
                    '<p><strong>Email Address:</strong> ' + (p.email || '—') + '</p>';
            }
        }
    }

    function findSectionByFormId(formId) {
        if (!window.OGS_SECTIONS) return null;
        for (var i = 0; i < window.OGS_SECTIONS.length; i++) {
            if (window.OGS_SECTIONS[i].form === formId) return window.OGS_SECTIONS[i];
        }
        return null;
    }

    function resolveForm(formId) {
        var el = document.getElementById(formId);
        if (!el) return null;
        return el.tagName === 'FORM' ? el : el.querySelector('form');
    }

    function cwSaveSection(formId, options) {
        options = options || {};
        var form = resolveForm(formId);
        if (!form) return Promise.reject(new Error('Form not found'));

        prepareBasicLocationPayload(form);
        if (typeof window.cwSyncSelect2BeforeSubmit === 'function') {
            window.cwSyncSelect2BeforeSubmit(form);
        }
        prepareJobFormPayload(form);

        var formData = new FormData(form);
        if (!formData.has('_method')) {
            formData.append('_method', 'PUT');
        }

        var submitBtn = options.trigger || form.querySelector('[type="submit"]');
        if (submitBtn) submitBtn.disabled = true;

        return fetch(form.getAttribute('action') || saveUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken(),
            },
        })
            .then(function (res) {
                return res.json().then(function (body) {
                    if (!res.ok) {
                        var err = new Error((body && body.message) || 'Save failed');
                        err.response = body;
                        err.status = res.status;
                        throw err;
                    }
                    return body;
                });
            })
            .then(function (data) {
                if (data.success) {
                    toast('success', data.message || 'Saved');
                    updateCompletionBar(data);
                    applySectionPreview(data);
                    if (!options.keepOpen) {
                        var section = findSectionByFormId(formId);
                        if (section && typeof window.ogsCloseSection === 'function') {
                            window.ogsCloseSection(section);
                        }
                    }
                }
                return data;
            })
            .catch(function (err) {
                var msg = (err.response && err.response.message) || err.message || 'Save failed';
                if (err.response && err.response.errors) {
                    var first = Object.values(err.response.errors)[0];
                    if (Array.isArray(first) && first[0]) msg = first[0];
                }
                toast('error', msg);
                throw err;
            })
            .finally(function () {
                if (submitBtn) submitBtn.disabled = false;
            });
    }

    function cwAutoSaveForm(formId) {
        return cwSaveSection(formId, { keepOpen: true });
    }

    function debounce(fn, ms) {
        var t;
        return function () {
            var args = arguments;
            var ctx = this;
            clearTimeout(t);
            t = setTimeout(function () { fn.apply(ctx, args); }, ms);
        };
    }

    function cwSubmitModalForm(form, options) {
        options = options || {};
        if (!form) return Promise.reject(new Error('No form'));

        var formData = new FormData(form);
        var submitBtn = form.querySelector('[type="submit"]');
        if (submitBtn) submitBtn.disabled = true;

        return fetch(form.getAttribute('action'), {
            method: form.getAttribute('method') || 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken(),
            },
        })
            .then(function (res) {
                return res.json().then(function (body) {
                    if (!res.ok) {
                        var err = new Error((body && body.message) || 'Save failed');
                        err.response = body;
                        throw err;
                    }
                    return body;
                });
            })
            .then(function (data) {
                toast('success', data.message || 'Saved');
                if (data.completionPercentage !== undefined) {
                    updateCompletionBar(data);
                }
                if (options.onSuccess) options.onSuccess(data);
                if (options.modalId) {
                    $('#' + options.modalId).modal('hide');
                }
                return data;
            })
            .catch(function (err) {
                toast('error', (err.response && err.response.message) || err.message || 'Save failed');
                throw err;
            })
            .finally(function () {
                if (submitBtn) submitBtn.disabled = false;
            });
    }

    function cwDeleteRow(url, options) {
        options = options || {};
        return fetch(url, {
            method: 'POST',
            body: options.body || null,
            credentials: 'same-origin',
            headers: Object.assign({
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken(),
            }, options.headers || {}),
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.success !== false) {
                    toast('success', data.message || 'Deleted');
                    if (data.completionPercentage !== undefined) updateCompletionBar(data);
                    if (options.onSuccess) options.onSuccess(data);
                } else {
                    toast('error', data.message || 'Delete failed');
                }
                return data;
            });
    }

    function initSectionForms() {
        var sectionForms = [
            'basicForm', 'jobForm', 'summaryForm', 'skillForm',
            'languageForm', 'socialForm', 'accountForm', 'jobalertRolesForm',
        ];
        sectionForms.forEach(function (formId) {
            var form = resolveForm(formId);
            if (!form) return;
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                cwSaveSection(formId);
            });
        });
    }

    function initAutoSave() {
        var summaryForm = resolveForm('summaryForm');
        if (summaryForm) {
            var bio = summaryForm.querySelector('[name="bio"]');
            if (bio) {
                bio.addEventListener('blur', debounce(function () {
                    if (bio.value.trim()) cwAutoSaveForm('summaryForm');
                }, 300));
            }
        }

        var visibilityForm = document.getElementById('visibility');
        if (visibilityForm) {
            visibilityForm.addEventListener('change', function (e) {
                if (e.target.type === 'checkbox') {
                    cwAutoSaveForm('visibility');
                }
            });
            visibilityForm.addEventListener('submit', function (e) {
                e.preventDefault();
                cwAutoSaveForm('visibility');
            });
        }

        var alertForm = document.getElementById('alert');
        if (alertForm) {
            alertForm.addEventListener('change', function (e) {
                if (e.target.name === 'received_job_alert') {
                    cwSaveSection('alert', { keepOpen: true });
                }
            });
        }

        var socialContainer = document.getElementById('socialForm');
        if (socialContainer) {
            socialContainer.querySelectorAll('input[name="url[]"]').forEach(function (input) {
                input.addEventListener('blur', debounce(function () {
                    if (input.value.trim()) cwAutoSaveForm('socialForm');
                }, 400));
            });
        }
    }

    window.cwSaveSection = cwSaveSection;
    window.cwAutoSaveForm = cwAutoSaveForm;
    window.cwUpdateCompletionBar = updateCompletionBar;
    window.cwSubmitModalForm = cwSubmitModalForm;
    window.cwDeleteRow = cwDeleteRow;
    window.cwRefreshCsrfToken = refreshCsrfToken;
    window.cwApplyCsrfToken = applyCsrfToken;

    $(function () {
        initSectionForms();
        initAutoSave();
        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'visible') refreshCsrfToken();
        });
    });
})(window, window.jQuery);

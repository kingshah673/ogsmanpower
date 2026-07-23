/**
 * Employer job posting — upload advertisement PDF/image and auto-fill form or batch-post.
 */
(function (window, $) {
    'use strict';

    var lastParsedPayload = null;

    function showJobModal(modalEl) {
        if (!modalEl) {
            return;
        }
        if (window.jQuery && jQuery.fn.modal) {
            jQuery(modalEl).modal('show');
            return;
        }
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal && typeof bootstrap.Modal.getOrCreateInstance === 'function') {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
            return;
        }
        modalEl.classList.add('show');
        modalEl.style.display = 'block';
        modalEl.removeAttribute('aria-hidden');
        document.body.classList.add('modal-open');
    }

    function hideJobModal(modalEl) {
        if (!modalEl) {
            return;
        }
        if (window.jQuery && jQuery.fn.modal) {
            jQuery(modalEl).modal('hide');
            return;
        }
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal && typeof bootstrap.Modal.getOrCreateInstance === 'function') {
            bootstrap.Modal.getOrCreateInstance(modalEl).hide();
            return;
        }
        modalEl.classList.remove('show');
        modalEl.style.display = 'none';
        modalEl.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
    }

    function triggerJobDocUpload() {
        var input = document.getElementById('jobDocUpload');
        if (input) {
            input.click();
        }
    }

    function onJobDocSelected(input) {
        var file = input && input.files && input.files[0];
        var label = document.getElementById('jobDocText');
        if (file && label) {
            label.innerHTML = '&#10003; ' + file.name;
            label.classList.add('upload-done');
        }
    }

    function fillSelectByIdOrName(name, value) {
        if (!value) {
            return false;
        }
        var sel = document.querySelector('select[name="' + name + '"]');
        if (!sel) {
            return false;
        }
        var matched = null;
        Array.prototype.forEach.call(sel.options, function (opt) {
            if (String(opt.value) === String(value)) {
                matched = opt.value;
            }
        });
        if (matched !== null) {
            sel.value = matched;
            $(sel).trigger('change');
            return true;
        }
        return fillSelectByLabel(name, value);
    }

    function fillSelectByLabel(name, label) {
        if (!label) {
            return false;
        }
        var sel = document.querySelector('select[name="' + name + '"]');
        if (!sel) {
            return false;
        }
        var needle = String(label).toLowerCase();
        var matched = null;
        Array.prototype.forEach.call(sel.options, function (opt) {
            var text = (opt.text || '').toLowerCase();
            if (text === needle || text.indexOf(needle) !== -1 || needle.indexOf(text) !== -1) {
                matched = opt.value;
            }
        });
        if (matched !== null) {
            sel.value = matched;
            $(sel).trigger('change');
            return true;
        }
        return false;
    }

    function fillJobTitle(title) {
        if (!title) {
            return false;
        }
        var select = document.querySelector('select[name="title"]');
        var custom = document.getElementById('custom_product');
        if (!select) {
            return false;
        }
        var needle = title.trim().toLowerCase();
        var matched = false;
        Array.prototype.forEach.call(select.options, function (opt) {
            if (opt.value && opt.value !== 'custom' && (opt.text || '').trim().toLowerCase() === needle) {
                select.value = opt.value;
                matched = true;
            }
        });
        if (!matched) {
            select.value = 'custom';
            if (custom) {
                custom.style.display = 'block';
                custom.value = title;
            }
        } else if (custom) {
            custom.style.display = 'none';
            custom.value = '';
        }
        $(select).trigger('change');
        return true;
    }

    function fillInput(name, value) {
        if (value === null || value === undefined || value === '') {
            return false;
        }
        var el = document.querySelector('[name="' + name + '"]');
        if (!el) {
            return false;
        }
        el.value = value;
        el.dispatchEvent(new Event('input', { bubbles: true }));
        return true;
    }

    function fillLookupMulti(selectName, values, lookupType) {
        if (!values || !values.length) {
            return Promise.resolve(false);
        }
        var $el = $('select[name="' + selectName + '"]');
        if (!$el.length) {
            return Promise.resolve(false);
        }
        var baseUrl = window.cwSettingsLookupUrl || '';
        var chain = Promise.resolve();
        values.forEach(function (val) {
            if (!val) {
                return;
            }
            chain = chain.then(function () {
                var found = false;
                $el.find('option').each(function () {
                    if (($(this).text() || '').trim().toLowerCase() === String(val).toLowerCase()) {
                        $(this).prop('selected', true);
                        found = true;
                        return false;
                    }
                });
                if (found) {
                    return;
                }
                if (!baseUrl || !lookupType) {
                    $el.append(new Option(val, val, true, true));
                    return;
                }
                return fetch(baseUrl + '/' + lookupType + '?q=' + encodeURIComponent(val), {
                    headers: { Accept: 'application/json' },
                })
                    .then(function (r) { return r.json(); })
                    .then(function (json) {
                        var results = (json && json.results) || [];
                        var match = results.find(function (row) {
                            return (row.text || '').toLowerCase() === String(val).toLowerCase();
                        }) || results[0];
                        if (match) {
                            $el.append(new Option(match.text, match.id, true, true));
                        } else {
                            $el.append(new Option(val, val, true, true));
                        }
                    })
                    .catch(function () {
                        $el.append(new Option(val, val, true, true));
                    });
            });
        });
        return chain.then(function () {
            $el.trigger('change');
            return true;
        });
    }

    function fillBenefits(benefitNames) {
        if (!benefitNames || !benefitNames.length) {
            return false;
        }
        var filled = false;
        benefitNames.forEach(function (name) {
            var needle = String(name).toLowerCase();
            $('#benefit_list label').each(function () {
                var text = ($(this).find('span').text() || '').trim().toLowerCase();
                if (text === needle || text.indexOf(needle) !== -1 || needle.indexOf(text) !== -1) {
                    $(this).find('input[type="checkbox"]').prop('checked', true);
                    filled = true;
                }
            });
        });
        return filled;
    }

    function showFilledSummary(labels) {
        var box = document.getElementById('jobExtractionSummary');
        var list = document.getElementById('jobFilledList');
        if (!box || !list) {
            return;
        }
        if (!labels.length) {
            box.style.display = 'none';
            return;
        }
        list.innerHTML = labels.map(function (l) {
            return '<span class="badge bg-light text-dark border me-1 mb-1">' + l + '</span>';
        }).join('');
        box.style.display = 'block';
    }

    function salaryLabel(job) {
        if (job.salary_mode === 'range' && (job.min_salary || job.max_salary)) {
            return [job.min_salary, job.max_salary].filter(Boolean).join(' - ') + ' ' + (job.currency || '');
        }
        return job.custom_salary || '—';
    }

    function fillFormFromParsedJob(d, filledOut) {
        var filled = filledOut || [];

        if (fillJobTitle(d.job_title)) {
            filled.push('Job title');
        }
        if (fillInput('title_ar', d.job_title_ar)) {
            filled.push('Arabic title');
        }
        if (d.category_id && fillSelectByIdOrName('category_id', d.category_id)) {
            filled.push('Industry');
        } else if (fillSelectByLabel('category_id', d.industry)) {
            filled.push('Industry');
        }
        if (d.role_id && fillSelectByIdOrName('role_id', d.role_id)) {
            filled.push('Job role');
        } else if (fillSelectByLabel('role_id', d.job_role)) {
            filled.push('Job role');
        }
        if (d.experience_id && fillSelectByIdOrName('experience', d.experience_id)) {
            filled.push('Experience');
        } else if (fillSelectByLabel('experience', d.experience)) {
            filled.push('Experience');
        }
        if (d.education_id && fillSelectByIdOrName('education', d.education_id)) {
            filled.push('Education');
        } else if (d.education && fillSelectByLabel('education', d.education)) {
            filled.push('Education');
        }
        if (d.job_type_id && fillSelectByIdOrName('job_type', d.job_type_id)) {
            filled.push('Job type');
        } else if (fillSelectByLabel('job_type', d.job_type)) {
            filled.push('Job type');
        }
        if (d.salary_type_id && fillSelectByIdOrName('salary_type', d.salary_type_id)) {
            filled.push('Salary type');
        } else if (fillSelectByLabel('salary_type', d.salary_type)) {
            filled.push('Salary type');
        }

        if (d.salary_mode === 'custom' && typeof window.salaryModeChange === 'function') {
            window.salaryModeChange('custom');
            if (fillInput('custom_salary', d.custom_salary || 'Competitive')) {
                filled.push('Salary');
            }
        } else {
            if (typeof window.salaryModeChange === 'function') {
                window.salaryModeChange('range');
            }
            if (fillInput('min_salary', d.min_salary)) {
                filled.push('Min salary');
            }
            if (fillInput('max_salary', d.max_salary)) {
                filled.push('Max salary');
            }
        }
        if (fillSelectByLabel('currency', d.currency)) {
            filled.push('Currency');
        }
        if (fillInput('vacancies', d.vacancies)) {
            filled.push('Vacancies');
        }
        if (fillInput('deadline', d.deadline)) {
            filled.push('Deadline');
        }
        if (fillSelectByLabel('gender', d.gender)) {
            filled.push('Gender');
        }
        if (fillInput('min_age', d.min_age)) {
            filled.push('Min age');
        }
        if (fillInput('max_age', d.max_age)) {
            filled.push('Max age');
        }
        if (d.is_remote) {
            var remote = document.getElementById('remoteWork');
            if (remote) {
                remote.checked = true;
                filled.push('Remote');
            }
        }
        if (fillInput('location', d.location) || fillInput('location', d.city)) {
            filled.push('Location');
        }
        var leaflet = document.getElementById('leaflet_search');
        if (leaflet && d.location) {
            leaflet.value = d.location;
        }
        if (fillInput('description_ar', d.description_ar)) {
            filled.push('Arabic description');
        }
        if (d.description && window.editorInstance) {
            window.editorInstance.setData(d.description);
            filled.push('Description');
        } else if (fillInput('description', d.description)) {
            filled.push('Description');
        }

        if (fillBenefits(d.benefits)) {
            filled.push('Benefits');
        }

        return Promise.all([
            fillLookupMulti('skills[]', d.skills, 'skills'),
            fillLookupMulti('tags[]', d.tags, 'tags'),
        ]).then(function (results) {
            if (results[0]) {
                filled.push('Skills');
            }
            if (results[1]) {
                filled.push('Tags');
            }
            return filled;
        });
    }

    function showBatchModal(payload) {
        var jobs = payload.jobs || [];
        var shared = payload.shared || {};
        var modalEl = document.getElementById('jobBatchModal');
        if (!modalEl || !jobs.length) {
            return;
        }

        var intro = document.getElementById('jobBatchModalIntro');
        var tbody = document.getElementById('jobBatchTableBody');
        var postLabel = document.getElementById('jobBatchPostAllLabel');

        if (intro) {
            var sharedBits = [];
            if (shared.country) {
                sharedBits.push(shared.country);
            }
            if (shared.deadline) {
                sharedBits.push('Deadline: ' + shared.deadline);
            }
            intro.textContent = jobs.length + ' positions found'
                + (sharedBits.length ? ' (' + sharedBits.join(' · ') + ')' : '')
                + '. Review below, then post all or fill the first job in the form.';
        }

        if (tbody) {
            tbody.innerHTML = jobs.map(function (job, i) {
                return '<tr>'
                    + '<td>' + (i + 1) + '</td>'
                    + '<td>' + escapeHtml(job.job_title || '—') + '</td>'
                    + '<td>' + escapeHtml(String(job.vacancies || 1)) + '</td>'
                    + '<td>' + escapeHtml(salaryLabel(job)) + '</td>'
                    + '<td>' + escapeHtml(job.location || job.country || shared.country || '—') + '</td>'
                    + '</tr>';
            }).join('');
        }

        if (postLabel) {
            postLabel.textContent = 'Post all ' + jobs.length + ' jobs';
        }

        showJobModal(modalEl);
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function postAllParsedJobs() {
        if (!lastParsedPayload || !window.cwJobAiBatchStoreUrl) {
            alert('No parsed jobs to post.');
            return;
        }

        var btn = document.getElementById('jobBatchPostAllBtn');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Posting...';
        }

        fetch(window.cwJobAiBatchStoreUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') || {}).content || '',
                Accept: 'application/json',
            },
            body: JSON.stringify({
                jobs: lastParsedPayload.jobs,
                shared: lastParsedPayload.shared || {},
            }),
        })
            .then(function (res) {
                return res.text().then(function (text) {
                    try {
                        return { status: res.status, data: JSON.parse(text) };
                    } catch (e) {
                        throw new Error('Server error ' + res.status + ': ' + text.substring(0, 300));
                    }
                });
            })
            .then(function (r) {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-upload mr-1"></i> <span id="jobBatchPostAllLabel">Post all jobs</span>';
                }

                var res = r.data;
                if (res.error) {
                    var detail = '';
                    if (res.result && res.result.failed && res.result.failed.length) {
                        detail = '\n\n' + res.result.failed.map(function (f) {
                            return (f.title || 'Job') + ': ' + (f.message || 'Failed');
                        }).join('\n');
                    }
                    alert((res.message || 'Could not post jobs.') + detail);
                    return;
                }

                hideJobModal(document.getElementById('jobBatchModal'));

                var created = (res.result && res.result.created) || [];
                var skipped = (res.result && res.result.skipped) || [];
                var failed = (res.result && res.result.failed) || [];

                var msg = res.message || (created.length + ' job(s) posted.');
                if (skipped.length) {
                    msg += '\n\nSkipped (plan limit): ' + skipped.map(function (s) { return s.title; }).join(', ');
                }
                if (failed.length) {
                    msg += '\n\nFailed: ' + failed.map(function (f) { return f.title; }).join(', ');
                }

                if (typeof window.toastr !== 'undefined') {
                    window.toastr.success(msg, 'Batch posting complete');
                } else {
                    alert(msg);
                }

                if (created.length === 1 && created[0].slug) {
                    window.location.href = '/company/promote/job/' + created[0].slug;
                } else if (created.length > 0) {
                    window.location.href = '/company/my-jobs';
                }
            })
            .catch(function (err) {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-upload mr-1"></i> <span id="jobBatchPostAllLabel">Post all jobs</span>';
                }
                console.error('[Job AI batch]', err);
                alert('Network error — batch posting failed.');
            });
    }

    function uploadJobPosting() {
        var fileInput = document.getElementById('jobDocUpload');
        var file = fileInput && fileInput.files && fileInput.files[0];
        if (!file) {
            alert('Please select a job advertisement file first.');
            return;
        }
        var loader = document.getElementById('jobAiLoader');
        if (loader) {
            loader.classList.remove('d-none');
        }

        var formData = new FormData();
        formData.append('job_document', file);

        fetch(window.cwJobAiParseUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') || {}).content || '',
                Accept: 'application/json',
            },
            body: formData,
        })
            .then(function (res) {
                return res.text().then(function (text) {
                    try {
                        return { status: res.status, data: JSON.parse(text) };
                    } catch (e) {
                        throw new Error('Server error ' + res.status + ': ' + text.substring(0, 300));
                    }
                });
            })
            .then(function (r) {
                if (loader) {
                    loader.classList.add('d-none');
                }
                var res = r.data;
                if (res.error) {
                    var msgs = {
                        not_job_posting: 'This file does not appear to be a job advertisement.',
                        unreadable: 'Cannot read text from this file. Try a clearer PDF or photo.',
                        ai_unconfigured: res.message || 'AI is not configured on this server.',
                    };
                    alert(msgs[res.error] || res.message || 'AI extraction failed.');
                    return;
                }
                var payload = res.data;
                if (!payload) {
                    alert('AI returned no data. Please try again.');
                    return;
                }

                lastParsedPayload = payload;
                var jobs = payload.jobs || [];
                var jobCount = payload.job_count || jobs.length || 0;

                if (jobCount > 1) {
                    showBatchModal(payload);
                    return;
                }

                var single = jobs[0] || payload;
                fillFormFromParsedJob(single, []).then(function (filled) {
                    showFilledSummary(filled);
                    if (typeof window.toastr !== 'undefined') {
                        window.toastr.success('Job form filled from advertisement. Review and submit.', 'Extraction complete');
                    } else {
                        alert('Job form filled. Please review all fields before posting.');
                    }
                });
            })
            .catch(function (err) {
                if (loader) {
                    loader.classList.add('d-none');
                }
                console.error('[Job AI]', err);
                alert('Network error — job extraction failed.');
            });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var fillFirstBtn = document.getElementById('jobBatchFillFirstBtn');
        if (fillFirstBtn) {
            fillFirstBtn.addEventListener('click', function () {
                if (!lastParsedPayload || !lastParsedPayload.jobs || !lastParsedPayload.jobs[0]) {
                    return;
                }
                fillFormFromParsedJob(lastParsedPayload.jobs[0], []).then(function (filled) {
                    showFilledSummary(filled);
                    hideJobModal(document.getElementById('jobBatchModal'));
                    if (typeof window.toastr !== 'undefined') {
                        window.toastr.info('First job loaded into the form. Review and submit, or post all from the upload panel.', 'Form filled');
                    }
                });
            });
        }

        var postAllBtn = document.getElementById('jobBatchPostAllBtn');
        if (postAllBtn) {
            postAllBtn.addEventListener('click', postAllParsedJobs);
        }
    });

    window.triggerJobDocUpload = triggerJobDocUpload;
    window.onJobDocSelected = onJobDocSelected;
    window.uploadJobPosting = uploadJobPosting;
})(window, window.jQuery);

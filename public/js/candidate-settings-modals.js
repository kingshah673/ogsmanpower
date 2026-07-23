/**
 * Candidate settings — experience, education, resume modals (AJAX, no reload).
 */
(function (window, $) {
    'use strict';

    function csrf() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function escapeHtml(str) {
        return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function experienceRowHtml(item) {
        var encoded = encodeURIComponent(JSON.stringify(item));
        return '<tr data-id="' + item.id + '">' +
            '<td>' + escapeHtml(item.company) + '</td>' +
            '<td>' + escapeHtml(item.department) + '</td>' +
            '<td>' + escapeHtml(item.designation) + '</td>' +
            '<td class="text-nowrap">' + escapeHtml(item.period) + '</td>' +
            '<td class="text-center text-nowrap">' +
            '<a href="javascript:void(0)" class="btn btn-sm btn-outline-primary me-1 cw-edit-experience" data-item="' + encoded + '"><i class="fas fa-edit"></i></a>' +
            '<button type="button" class="btn btn-sm btn-outline-danger cw-delete-experience" data-id="' + item.id + '" data-url="' + escapeHtml((window.cwModalRoutes.experienceDelete || '').replace('__ID__', item.id)) + '"><i class="fas fa-trash"></i></button>' +
            '</td></tr>';
    }

    function educationRowHtml(item) {
        var encoded = encodeURIComponent(JSON.stringify(item));
        return '<tr data-id="' + item.id + '">' +
            '<td>' + escapeHtml(item.level) + '</td>' +
            '<td>' + escapeHtml(item.degree) + '</td>' +
            '<td>' + escapeHtml(item.year) + '</td>' +
            '<td class="text-center text-nowrap">' +
            '<a href="javascript:void(0)" class="btn btn-sm btn-outline-primary me-1 cw-edit-education" data-item="' + encoded + '"><i class="fas fa-edit"></i></a>' +
            '<button type="button" class="btn btn-sm btn-outline-danger cw-delete-education" data-id="' + item.id + '" data-url="' + escapeHtml((window.cwModalRoutes.educationDelete || '').replace('__ID__', item.id)) + '"><i class="fas fa-trash"></i></button>' +
            '</td></tr>';
    }

    function upsertRow(tbodyId, item, addRowHtml) {
        var tbody = document.getElementById(tbodyId);
        if (!tbody || !item) return;
        var existing = tbody.querySelector('tr[data-id="' + item.id + '"]');
        var addBtn = tbody.querySelector('#addExperience, #addEducation');
        var addRow = addBtn ? addBtn.closest('tr') : null;
        var html = addRowHtml(item);
        if (existing) {
            existing.outerHTML = html;
        } else {
            var empty = tbody.querySelector('td[colspan]');
            if (empty) empty.closest('tr').remove();
            if (addRow) {
                addRow.insertAdjacentHTML('beforebegin', html);
            } else {
                tbody.insertAdjacentHTML('beforeend', html);
            }
        }
    }

    function removeRow(tbodyId, id) {
        var tbody = document.getElementById(tbodyId);
        if (!tbody) return;
        var row = tbody.querySelector('tr[data-id="' + id + '"]');
        if (row) row.remove();
        if (!tbody.querySelector('tr[data-id]')) {
            tbody.insertAdjacentHTML('afterbegin',
                '<tr><td colspan="5" class="text-center py-4"><p class="mt-2">No data found</p></td></tr>');
        }
    }

    function bindModalForm(selector, options) {
        document.querySelectorAll(selector).forEach(function (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                if (typeof window.cwSubmitModalForm !== 'function') return;
                window.cwSubmitModalForm(form, options);
            });
        });
    }

    function bindRowActions() {
        document.addEventListener('click', function (e) {
            var editExp = e.target.closest('.cw-edit-experience');
            if (editExp && typeof window.experienceDetail === 'function') {
                e.preventDefault();
                var item = JSON.parse(decodeURIComponent(editExp.getAttribute('data-item')));
                window.experienceDetail(item, item.start_formatted || '', item.end_formatted || '');
                return;
            }
            var editEdu = e.target.closest('.cw-edit-education');
            if (editEdu && typeof window.educationDetail === 'function') {
                e.preventDefault();
                var edu = JSON.parse(decodeURIComponent(editEdu.getAttribute('data-item')));
                window.educationDetail(edu);
                return;
            }

            var btn = e.target.closest('.cw-delete-experience, .cw-delete-education');
            if (!btn) return;
            e.preventDefault();
            if (!confirm('Are you sure you want to delete this item?')) return;
            var url = btn.getAttribute('data-url');
            var id = btn.getAttribute('data-id');
            var isExp = btn.classList.contains('cw-delete-experience');
            fetch(url, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrf(),
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: '_method=DELETE&_token=' + encodeURIComponent(csrf()),
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.success) {
                        removeRow(isExp ? 'cwExperienceRows' : 'cwEducationRows', id);
                        if (window.cwUpdateCompletionBar) window.cwUpdateCompletionBar(data);
                        if (window.toastr) toastr.success(data.message);
                    }
                });

        });
    }

    $(function () {
        bindModalForm('#addExperienceModal form', {
            modalId: 'addExperienceModal',
            onSuccess: function (data) {
                if (data.item) upsertRow('cwExperienceRows', data.item, experienceRowHtml);
                if (window.cwUpdateCompletionBar) window.cwUpdateCompletionBar(data);
                if (typeof closeAddExperienceModal === 'function') closeAddExperienceModal();
            },
        });
        bindModalForm('#editExperienceModal form', {
            modalId: 'editExperienceModal',
            onSuccess: function (data) {
                if (data.item) upsertRow('cwExperienceRows', data.item, experienceRowHtml);
                if (window.cwUpdateCompletionBar) window.cwUpdateCompletionBar(data);
                if (typeof closeEditExperienceModal === 'function') closeEditExperienceModal();
            },
        });
        bindModalForm('#addEducationModal form', {
            modalId: 'addEducationModal',
            onSuccess: function (data) {
                if (data.item) upsertRow('cwEducationRows', data.item, educationRowHtml);
                if (window.cwUpdateCompletionBar) window.cwUpdateCompletionBar(data);
                if (typeof closeAddEducationModal === 'function') closeAddEducationModal();
            },
        });
        bindModalForm('#editEducationModal form', {
            modalId: 'editEducationModal',
            onSuccess: function (data) {
                if (data.item) upsertRow('cwEducationRows', data.item, educationRowHtml);
                if (window.cwUpdateCompletionBar) window.cwUpdateCompletionBar(data);
                if (typeof closeEditEducationModal === 'function') closeEditEducationModal();
            },
        });
        bindModalForm('#resumeModal form', {
            modalId: 'resumeModal',
            onSuccess: function () { $('#resumeModal').modal('hide'); },
        });
        bindModalForm('#resumeEditModal form', {
            modalId: 'resumeEditModal',
            onSuccess: function () { $('#resumeEditModal').modal('hide'); },
        });

        bindRowActions();

        document.querySelectorAll('#cwExperienceRows form[method="POST"], #cwEducationRows form[method="POST"]').forEach(function (form) {
            form.addEventListener('submit', function (ev) {
                ev.preventDefault();
                if (!confirm('Are you sure you want to delete this item?')) return;
                var fd = new FormData(form);
                fetch(form.action, {
                    method: 'POST',
                    body: fd,
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (data.success) {
                            var row = form.closest('tr');
                            if (row) row.remove();
                            if (window.cwUpdateCompletionBar) window.cwUpdateCompletionBar(data);
                            if (window.toastr) toastr.success(data.message);
                        }
                    });
            });
        });
    });
})(window, window.jQuery);

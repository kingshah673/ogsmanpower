/**
 * Employer post/edit job form — Select2 + chapter nav (aligned with seeker settings).
 */
(function (window, $) {
    'use strict';

    function initChapterNav() {
        var sections = document.querySelectorAll('.seeker-settings-page .form-section-anchor');
        var links = document.querySelectorAll('.seeker-settings-page .cw-chapter-link');
        if (!sections.length || !links.length) {
            return;
        }

        function setActiveChapter() {
            var current = '';
            var offset = 120;
            sections.forEach(function (section) {
                if (window.scrollY >= section.offsetTop - offset) {
                    current = section.id;
                }
            });
            links.forEach(function (link) {
                link.classList.toggle('is-active', link.getAttribute('href') === '#' + current);
            });
        }

        window.addEventListener('scroll', setActiveChapter, { passive: true });
        setActiveChapter();
    }

    function initJobFormSelects() {
        var root = document.getElementById('company-job-form');
        if (window.cwInitSettingsSelects) {
            window.cwInitSettingsSelects(root || document);
        }
    }

    function bindFormSync() {
        var form = document.getElementById('company-job-form');
        if (!form || !window.cwSyncSelect2BeforeSubmit) {
            return;
        }
        form.addEventListener('submit', function () {
            window.cwSyncSelect2BeforeSubmit(form);
        });
    }

    $(function () {
        initJobFormSelects();
        initChapterNav();
        bindFormSync();

        window.addEventListener('render-select2', function () {
            initJobFormSelects();
        });
    });
})(window, window.jQuery);

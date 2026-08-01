(function () {
    function activateTab(tabId) {
        document.querySelectorAll('.cs-tab').forEach(function (tab) {
            var active = tab.getAttribute('data-tab') === tabId;
            tab.classList.toggle('is-active', active);
            tab.setAttribute('aria-selected', active ? 'true' : 'false');
            tab.setAttribute('tabindex', active ? '0' : '-1');
        });
        document.querySelectorAll('.cs-tab-panel').forEach(function (panel) {
            var active = panel.getAttribute('data-panel') === tabId;
            panel.classList.toggle('is-active', active);
            panel.hidden = !active;
        });
        if (tabId === 'assets' && typeof window.scheduleCaseAssetsLoad === 'function') {
            window.scheduleCaseAssetsLoad();
        }
        if (tabId === 'comments' && typeof window.loadCaseComments === 'function') {
            window.loadCaseComments();
        }
        if (tabId === 'activities' && typeof window.loadMoreCaseActivitiesIfNeeded === 'function') {
            window.loadMoreCaseActivitiesIfNeeded();
        }
    }

    window.activateCaseShowTab = activateTab;

    var tabs = Array.prototype.slice.call(document.querySelectorAll('.cs-tab'));
    if (!tabs.length) return;

    tabs.forEach(function (tab, index) {
        tab.setAttribute('tabindex', tab.classList.contains('is-active') ? '0' : '-1');
        tab.addEventListener('click', function () {
            activateTab(tab.getAttribute('data-tab'));
        });
        tab.addEventListener('keydown', function (e) {
            var targetTab = null;
            if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
                e.preventDefault();
                targetTab = tabs[(index + 1) % tabs.length];
            } else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
                e.preventDefault();
                targetTab = tabs[(index - 1 + tabs.length) % tabs.length];
            } else if (e.key === 'Home') {
                e.preventDefault();
                targetTab = tabs[0];
            } else if (e.key === 'End') {
                e.preventDefault();
                targetTab = tabs[tabs.length - 1];
            }
            if (targetTab) {
                activateTab(targetTab.getAttribute('data-tab'));
                targetTab.focus();
            }
        });
    });

    document.querySelectorAll('[data-jump-tab]').forEach(function (el) {
        el.addEventListener('click', function () {
            activateTab(el.getAttribute('data-jump-tab'));
            var tabButton = document.querySelector('.cs-tab[data-tab="' + el.getAttribute('data-jump-tab') + '"]');
            if (tabButton) tabButton.focus();
        });
    });
})();

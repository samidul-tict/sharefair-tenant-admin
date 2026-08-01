document.addEventListener('DOMContentLoaded', function () {
    var wrapper = document.getElementById('casePartiesWrapper');
    if (!wrapper) return;

    var additionalList = document.getElementById('casePartiesAdditional');
    var addBtn = document.getElementById('casePartiesAddBtn');
    var fieldPrefix = wrapper.getAttribute('data-field-prefix') || 'contacts';
    var roleField = wrapper.getAttribute('data-role-field') || 'role_id';
    var searchUrl = wrapper.getAttribute('data-search-url') || '';

    var PL = 'PL';
    var DEF = 'DEF';
    var typeaheadTimeouts = {};
    var typeaheadState = {};

    function setTypeaheadOpen(resultsEl, open) {
        if (!resultsEl) return;
        resultsEl.setAttribute('aria-hidden', open ? 'false' : 'true');
        var wrap = resultsEl.closest('.cc-typeahead-wrap');
        var block = resultsEl.closest('.cc-party-block');
        if (wrap) wrap.classList.toggle('cc-typeahead-open', open);
        if (block) block.classList.toggle('cc-typeahead-active', open);
    }

    function updateComboboxExpanded(inputEl, resultsEl, open) {
        if (!inputEl) return;
        inputEl.setAttribute('aria-expanded', open ? 'true' : 'false');
        setTypeaheadOpen(resultsEl, open);
    }

    function partyBlock(type) {
        return wrapper.querySelector('[data-party-block="' + type + '"]');
    }

    function blockNameInput(type) {
        var block = partyBlock(type);
        return block ? block.querySelector('.cc-party-name-input') : null;
    }

    function blockNameValue(type) {
        var input = blockNameInput(type);
        return input && input.value.trim() ? input.value.trim() : '[' + (type === 'client' ? 'Client name' : 'Spouse name') + ']';
    }

    function counselNameValue(type) {
        var block = partyBlock(type);
        var input = block && block.querySelector('.cc-party-name-input');
        return input && input.value.trim() ? input.value.trim() : '[Attorney name]';
    }

    function updateLegalCaptions() {
        var clientCaption = wrapper.querySelector('[data-caption-for="client_counsel"]');
        var spouseCaption = wrapper.querySelector('[data-caption-for="spouse_counsel"]');
        if (clientCaption) {
            clientCaption.textContent = 'I, ' + counselNameValue('client_counsel') + ', representing ' + blockNameValue('client') + '.';
        }
        if (spouseCaption) {
            spouseCaption.textContent = 'I, ' + counselNameValue('spouse_counsel') + ', representing ' + blockNameValue('spouse') + '.';
        }
    }

    function updateDistributionValueCaps() {
        var methodSelect = document.getElementById('distribution_method');
        var requiresCap = methodSelect && (methodSelect.value === 'DIST_FCP' || methodSelect.value === 'DIST_CAP');

        wrapper.querySelectorAll('.cc-party-block').forEach(function (block) {
            var roleInput = block.querySelector('input[type="hidden"][name*="[' + roleField + ']"]');
            var role = roleInput ? roleInput.value : '';
            var capField = block.querySelector('[data-distribution-cap-field]');
            var capInput = capField && capField.querySelector('input[name*="[distribution_value_cap]"]');
            var isClientOrSpouse = role === PL || role === DEF;
            var show = Boolean(requiresCap && isClientOrSpouse);

            if (capField) capField.hidden = !show;
            if (capInput) {
                capInput.required = show;
                capInput.setAttribute('aria-required', show ? 'true' : 'false');
            }
        });
    }

    function nextAdditionalIndex() {
        var max = 3;
        wrapper.querySelectorAll('.cc-party-block').forEach(function (block) {
            var idx = parseInt(block.getAttribute('data-contact-index'), 10);
            if (!isNaN(idx) && idx > max) max = idx;
        });
        return max + 1;
    }

    function getTypeaheadElements(block) {
        return {
            userIdInput: block.querySelector('.cc-user-id-input'),
            emailInput: block.querySelector('input[name*="[email]"]'),
            nameInput: block.querySelector('.cc-party-name-input'),
            phoneInput: block.querySelector('input[name*="[phone]"]'),
            searchInput: block.querySelector('.cc-user-search-input'),
            resultsEl: block.querySelector('.cc-typeahead-results'),
        };
    }

    function wireTypeaheadCombobox(block) {
        var searchInput = block.querySelector('.cc-user-search-input');
        var resultsEl = block.querySelector('.cc-typeahead-results');
        if (!searchInput || !resultsEl || searchInput.disabled) return;
        searchInput.setAttribute('role', 'combobox');
        searchInput.setAttribute('aria-autocomplete', 'list');
        searchInput.setAttribute('aria-expanded', 'false');
        searchInput.setAttribute('aria-controls', resultsEl.id || '');
    }

    function highlightTypeaheadOption(inputEl, index) {
        var idx = inputEl.getAttribute('data-contact-index');
        var state = typeaheadState[idx];
        if (!state) return;
        var block = inputEl.closest('.cc-party-block');
        var resultsEl = block && block.querySelector('.cc-typeahead-results');
        if (!resultsEl) return;
        var options = resultsEl.querySelectorAll('[role="option"]');
        state.activeIndex = index;
        options.forEach(function (opt, i) {
            var active = i === index;
            opt.classList.toggle('is-active', active);
            opt.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        if (index >= 0 && options[index]) {
            inputEl.setAttribute('aria-activedescendant', options[index].id);
            options[index].scrollIntoView({ block: 'nearest' });
        } else {
            inputEl.removeAttribute('aria-activedescendant');
        }
    }

    function selectTypeaheadOption(inputEl, optionEl) {
        if (!optionEl || !optionEl.dataset.id) return;
        var block = inputEl.closest('.cc-party-block');
        var els = getTypeaheadElements(block);
        if (els.userIdInput) els.userIdInput.value = optionEl.dataset.id;
        if (els.emailInput) els.emailInput.value = optionEl.dataset.email || '';
        if (els.nameInput) els.nameInput.value = optionEl.dataset.name || '';
        if (els.phoneInput) els.phoneInput.value = optionEl.dataset.phone || '';
        inputEl.value = (optionEl.dataset.name || '') + ' (' + (optionEl.dataset.email || '') + ')';
        if (els.resultsEl) {
            els.resultsEl.innerHTML = '';
            updateComboboxExpanded(inputEl, els.resultsEl, false);
        }
        inputEl.removeAttribute('aria-activedescendant');
        var idx = inputEl.getAttribute('data-contact-index');
        if (idx) typeaheadState[idx] = { activeIndex: -1, items: [] };
        updateLegalCaptions();
    }

    function renderTypeaheadResults(inputEl, users) {
        var idx = inputEl.getAttribute('data-contact-index');
        var block = inputEl.closest('.cc-party-block');
        var resultsEl = block && block.querySelector('.cc-typeahead-results');
        if (!resultsEl) return;

        resultsEl.innerHTML = '';
        typeaheadState[idx] = { activeIndex: -1, items: users || [] };

        if (!users || users.length === 0) {
            resultsEl.innerHTML = '<div class="cc-typeahead-item cc-typeahead-empty" role="status">No users found</div>';
            updateComboboxExpanded(inputEl, resultsEl, true);
            return;
        }

        users.forEach(function (u, i) {
            var div = document.createElement('div');
            div.className = 'cc-typeahead-item';
            div.id = (resultsEl.id || 'results') + '_opt_' + i;
            div.setAttribute('role', 'option');
            div.setAttribute('aria-selected', 'false');
            div.textContent = u.name + ' (' + (u.email || '') + ')';
            div.dataset.id = u.id;
            div.dataset.name = u.name || '';
            div.dataset.email = u.email || '';
            div.dataset.phone = u.phone_number != null ? u.phone_number : '';
            div.addEventListener('mousedown', function (e) {
                e.preventDefault();
            });
            div.addEventListener('click', function () {
                selectTypeaheadOption(inputEl, div);
            });
            resultsEl.appendChild(div);
        });
        updateComboboxExpanded(inputEl, resultsEl, true);
    }

    function buildAdditionalRow(index) {
        var proto = document.getElementById('casePartyAdditionalPrototype');
        if (proto) {
            var html = proto.innerHTML.replace(/__INDEX__/g, String(index));
            var temp = document.createElement('div');
            temp.innerHTML = html.trim();
            var article = temp.querySelector('.cc-party-block') || temp.firstElementChild;
            if (article) {
                article.setAttribute('data-contact-index', String(index));
                wireTypeaheadCombobox(article);
                return article;
            }
        }
        return null;
    }

    function onUserSearchInput(inputEl) {
        var block = inputEl.closest('.cc-party-block');
        if (!block) return;
        var idx = inputEl.getAttribute('data-contact-index');
        var els = getTypeaheadElements(block);
        var val = (inputEl.value || '').trim();

        if (val.length < 2) {
            if (els.resultsEl) {
                els.resultsEl.innerHTML = '';
                updateComboboxExpanded(inputEl, els.resultsEl, false);
            }
            inputEl.removeAttribute('aria-activedescendant');
            if (idx) typeaheadState[idx] = { activeIndex: -1, items: [] };
            if (val === '' && els.userIdInput && els.userIdInput.value) {
                els.userIdInput.value = '';
                if (els.emailInput) els.emailInput.value = '';
                if (els.nameInput) els.nameInput.value = '';
                if (els.phoneInput) els.phoneInput.value = '';
                updateLegalCaptions();
            }
            return;
        }

        clearTimeout(typeaheadTimeouts[idx]);
        typeaheadTimeouts[idx] = setTimeout(function () {
            fetch(searchUrl + '?q=' + encodeURIComponent(val), {
                credentials: 'same-origin',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            })
                .then(function (r) {
                    if (!r.ok) throw new Error('Search failed');
                    return r.json();
                })
                .then(function (users) {
                    renderTypeaheadResults(inputEl, users);
                })
                .catch(function () {
                    if (!els.resultsEl) return;
                    els.resultsEl.innerHTML = '<div class="cc-typeahead-item cc-typeahead-empty" role="status">Search failed</div>';
                    updateComboboxExpanded(inputEl, els.resultsEl, true);
                });
        }, 300);
    }

    wrapper.querySelectorAll('.cc-party-block').forEach(wireTypeaheadCombobox);

    wrapper.addEventListener('input', function (e) {
        if (e.target && e.target.classList.contains('cc-user-search-input')) {
            onUserSearchInput(e.target);
        }
        if (
            e.target
            && (
                e.target.classList.contains('cc-party-name-input')
                || e.target.matches('input[name*="[email]"]')
                || e.target.matches('input[name*="[phone]"]')
            )
        ) {
            var block = e.target.closest('.cc-party-block');
            if (block) {
                var userIdInput = block.querySelector('.cc-user-id-input');
                var searchInput = block.querySelector('.cc-user-search-input');
                if (userIdInput && userIdInput.value) {
                    userIdInput.value = '';
                }
                if (searchInput && e.target !== searchInput && !e.target.classList.contains('cc-user-search-input')) {
                    searchInput.value = '';
                }
            }
        }
        if (e.target && e.target.classList.contains('cc-party-name-input')) {
            updateLegalCaptions();
        }
    });

    wrapper.addEventListener('keydown', function (e) {
        if (!e.target || !e.target.classList.contains('cc-user-search-input')) return;
        var inputEl = e.target;
        var idx = inputEl.getAttribute('data-contact-index');
        var state = typeaheadState[idx] || { activeIndex: -1, items: [] };
        var block = inputEl.closest('.cc-party-block');
        var resultsEl = block && block.querySelector('.cc-typeahead-results');
        var open = resultsEl && resultsEl.getAttribute('aria-hidden') === 'false';
        var options = resultsEl ? resultsEl.querySelectorAll('[role="option"]') : [];

        if (e.key === 'ArrowDown') {
            if (!open || !options.length) return;
            e.preventDefault();
            var next = state.activeIndex + 1;
            if (next >= options.length) next = 0;
            highlightTypeaheadOption(inputEl, next);
        } else if (e.key === 'ArrowUp') {
            if (!open || !options.length) return;
            e.preventDefault();
            var prev = state.activeIndex - 1;
            if (prev < 0) prev = options.length - 1;
            highlightTypeaheadOption(inputEl, prev);
        } else if (e.key === 'Enter') {
            if (open && state.activeIndex >= 0 && options[state.activeIndex]) {
                e.preventDefault();
                selectTypeaheadOption(inputEl, options[state.activeIndex]);
            }
        } else if (e.key === 'Escape') {
            if (!open || !resultsEl) return;
            e.preventDefault();
            resultsEl.innerHTML = '';
            updateComboboxExpanded(inputEl, resultsEl, false);
            inputEl.removeAttribute('aria-activedescendant');
            typeaheadState[idx] = { activeIndex: -1, items: [] };
        }
    });

    wrapper.addEventListener('focusout', function (e) {
        if (e.target && e.target.classList.contains('cc-user-search-input')) {
            var block = e.target.closest('.cc-party-block');
            var res = block && block.querySelector('.cc-typeahead-results');
            if (res) {
                setTimeout(function () {
                    res.innerHTML = '';
                    updateComboboxExpanded(e.target, res, false);
                    e.target.removeAttribute('aria-activedescendant');
                }, 200);
            }
        }
    });

    wrapper.addEventListener('click', function (e) {
        var btn = e.target && e.target.closest('.cc-btn-remove-contact');
        if (!btn || btn.disabled) return;
        var block = btn.closest('.cc-party-block');
        if (!block || block.getAttribute('data-party-block') !== 'additional') return;
        block.remove();
    });

    if (addBtn && additionalList) {
        addBtn.addEventListener('click', function () {
            var idx = nextAdditionalIndex();
            var row = buildAdditionalRow(idx);
            if (row) {
                additionalList.appendChild(row);
                updateDistributionValueCaps();
            }
        });
    }

    var distributionMethodSelect = document.getElementById('distribution_method');
    if (distributionMethodSelect) {
        distributionMethodSelect.addEventListener('change', updateDistributionValueCaps);
    }

    updateLegalCaptions();
    updateDistributionValueCaps();
});

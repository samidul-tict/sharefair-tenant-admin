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

    function setTypeaheadOpen(resultsEl, open) {
        if (!resultsEl) return;
        resultsEl.setAttribute('aria-hidden', open ? 'false' : 'true');
        var wrap = resultsEl.closest('.cc-typeahead-wrap');
        var block = resultsEl.closest('.cc-party-block');
        if (wrap) wrap.classList.toggle('cc-typeahead-open', open);
        if (block) block.classList.toggle('cc-typeahead-active', open);
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
            var roleSelect = block.querySelector('.cc-additional-role-select');
            var role = roleInput ? roleInput.value : (roleSelect ? roleSelect.value : '');
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

    function buildAdditionalRow(index) {
        var article = document.createElement('article');
        article.className = 'cc-party-block cc-party-block-additional';
        article.setAttribute('data-party-block', 'additional');
        article.setAttribute('data-contact-index', String(index));
        article.innerHTML =
            '<header class="cc-party-block-header"><h3 class="cc-party-block-title">Additional legal representative</h3></header>' +
            '<div class="cc-party-block-body">' +
            '<input type="hidden" name="' + fieldPrefix + '[' + index + '][' + roleField + ']" value="LEGAL_RE">' +
            '<div class="cc-form-row cc-party-fields-row">' +
            '<div class="cc-form-group cc-user-search-cell"><label for="' + fieldPrefix + '_' + index + '_user_search">Search user</label>' +
            '<div class="cc-typeahead-wrap"><input type="text" id="' + fieldPrefix + '_' + index + '_user_search" class="cc-user-search-input" data-contact-index="' + index + '" placeholder="Type name or email..." autocomplete="off" aria-label="Search user by name or email">' +
            '<div class="cc-typeahead-results" id="' + fieldPrefix + '_' + index + '_results" role="listbox" aria-hidden="true"></div></div>' +
            '<input type="hidden" name="' + fieldPrefix + '[' + index + '][user_id]" value="" class="cc-user-id-input cc-contact-user-id"></div>' +
            '<div class="cc-form-group"><label for="' + fieldPrefix + '_' + index + '_name">Full name <span class="cc-required-asterisk" aria-hidden="true">*</span></label>' +
            '<input type="text" id="' + fieldPrefix + '_' + index + '_name" name="' + fieldPrefix + '[' + index + '][name]" class="cc-party-name-input" placeholder="Enter full name" required aria-required="true"></div>' +
            '<div class="cc-form-group"><label for="' + fieldPrefix + '_' + index + '_email">Email <span class="cc-required-asterisk" aria-hidden="true">*</span></label>' +
            '<input type="email" id="' + fieldPrefix + '_' + index + '_email" name="' + fieldPrefix + '[' + index + '][email]" placeholder="email@example.com" required aria-required="true" autocomplete="off"></div>' +
            '<div class="cc-form-group"><label for="' + fieldPrefix + '_' + index + '_phone">Phone <span class="cc-required-asterisk" aria-hidden="true">*</span></label>' +
            '<input type="tel" id="' + fieldPrefix + '_' + index + '_phone" name="' + fieldPrefix + '[' + index + '][phone]" placeholder="(123) 456-7890" required aria-required="true" inputmode="tel"></div>' +
            '<div class="cc-form-group cc-distribution-cap-field" data-distribution-cap-field hidden><label for="' + fieldPrefix + '_' + index + '_distribution_value_cap">Distribution value cap <span class="cc-required-asterisk" aria-hidden="true">*</span></label>' +
            '<input type="number" id="' + fieldPrefix + '_' + index + '_distribution_value_cap" name="' + fieldPrefix + '[' + index + '][distribution_value_cap]" placeholder="Enter value cap" min="0" step="0.01" inputmode="decimal"></div>' +
            '<div class="cc-form-group cc-contact-remove-cell cc-remove-wrap"><label class="cc-label-invisible">&nbsp;</label>' +
            '<button type="button" class="cc-btn-remove-contact btn-action-icon btn-delete" aria-label="Remove legal representative" title="Remove legal representative">' +
            '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>' +
            '</button></div></div></div>';
        return article;
    }

    function onUserSearchInput(inputEl) {
        var block = inputEl.closest('.cc-party-block');
        if (!block) return;
        var idx = inputEl.getAttribute('data-contact-index');
        var resultsEl = block.querySelector('.cc-typeahead-results');
        var userIdInput = block.querySelector('.cc-user-id-input');
        var emailInput = block.querySelector('input[name*="[email]"]');
        var nameInput = block.querySelector('.cc-party-name-input');
        var phoneInput = block.querySelector('input[name*="[phone]"]');
        var val = (inputEl.value || '').trim();

        if (val.length < 2) {
            if (resultsEl) {
                resultsEl.innerHTML = '';
                setTypeaheadOpen(resultsEl, false);
            }
            if (val === '' && userIdInput && userIdInput.value) {
                userIdInput.value = '';
                if (emailInput) emailInput.value = '';
                if (nameInput) nameInput.value = '';
                if (phoneInput) phoneInput.value = '';
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
                    if (!resultsEl) return;
                    resultsEl.innerHTML = '';
                    setTypeaheadOpen(resultsEl, true);
                    if (users.length === 0) {
                        resultsEl.innerHTML = '<div class="cc-typeahead-item cc-typeahead-empty">No users found</div>';
                        return;
                    }
                    users.forEach(function (u) {
                        var div = document.createElement('div');
                        div.className = 'cc-typeahead-item';
                        div.setAttribute('role', 'option');
                        div.textContent = u.name + ' (' + (u.email || '') + ')';
                        div.dataset.id = u.id;
                        div.dataset.name = u.name || '';
                        div.dataset.email = u.email || '';
                        div.dataset.phone = u.phone_number != null ? u.phone_number : '';
                        div.addEventListener('click', function () {
                            if (userIdInput) userIdInput.value = this.dataset.id;
                            if (emailInput) emailInput.value = this.dataset.email;
                            if (nameInput) nameInput.value = this.dataset.name;
                            if (phoneInput) phoneInput.value = this.dataset.phone;
                            inputEl.value = this.dataset.name + ' (' + this.dataset.email + ')';
                            resultsEl.innerHTML = '';
                            setTypeaheadOpen(resultsEl, false);
                            updateLegalCaptions();
                        });
                        resultsEl.appendChild(div);
                    });
                })
                .catch(function () {
                    if (resultsEl) {
                        resultsEl.innerHTML = '<div class="cc-typeahead-item cc-typeahead-empty">Search failed</div>';
                        setTypeaheadOpen(resultsEl, true);
                    }
                });
        }, 300);
    }

    wrapper.addEventListener('input', function (e) {
        if (e.target && e.target.classList.contains('cc-user-search-input')) {
            onUserSearchInput(e.target);
        }
        if (
            e.target
            && (
                e.target.classList.contains('cc-party-name-input')
                || (e.target.matches('input[name*="[email]"]'))
                || (e.target.matches('input[name*="[phone]"]'))
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

    wrapper.addEventListener('focusout', function (e) {
        if (e.target && e.target.classList.contains('cc-user-search-input')) {
            var block = e.target.closest('.cc-party-block');
            var res = block && block.querySelector('.cc-typeahead-results');
            if (res) {
                setTimeout(function () {
                    res.innerHTML = '';
                    setTypeaheadOpen(res, false);
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
            additionalList.appendChild(buildAdditionalRow(idx));
            updateDistributionValueCaps();
        });
    }

    var distributionMethodSelect = document.getElementById('distribution_method');
    if (distributionMethodSelect) {
        distributionMethodSelect.addEventListener('change', updateDistributionValueCaps);
    }

    updateLegalCaptions();
    updateDistributionValueCaps();
});

@extends('backend.layout.inner-app')
@section('title', 'Create Case | Share Fair')
@section('proxima')

<div class="case-create-modern">
    <div class="cc-page-container">
        <header class="cc-page-header">
            <nav class="cc-breadcrumb" aria-label="Breadcrumb">
                <a href="{{ route('admin.dashboard') }}">Dashboard</a>
                <span class="cc-breadcrumb-sep" aria-hidden="true">/</span>
                <a href="{{ route('admin.cases.index') }}">Cases</a>
                <span class="cc-breadcrumb-sep" aria-hidden="true">/</span>
                <span class="cc-breadcrumb-current">New Case</span>
            </nav>
        </header>

        @if($errors->any())
            <div class="cc-alert cc-alert-danger" role="alert">
                <ul class="mb-0 pl-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="cc-form-container">
            <div class="cc-required-notice" role="status">
                Fields marked with an asterisk are required
            </div>

            <form method="POST" action="{{ route('admin.cases.store') }}" class="cc-form-grid" aria-label="Create new case">
                @csrf

                <div class="cc-form-row">
                    <div class="cc-form-group">
                        <label for="case_number">
                            Case Number
                            <span class="cc-required-asterisk" aria-hidden="true">*</span>
                        </label>
                        <input type="text" id="case_number" name="case_number" value="{{ old('case_number') }}" placeholder="Enter case number" required aria-required="true">
                    </div>
                    <div class="cc-form-group">
                        <label for="case_type">
                            Case Type
                            <span class="cc-required-asterisk" aria-hidden="true">*</span>
                        </label>
                        <select id="case_type" name="case_type" required aria-required="true">
                            <option value="">Select Case Type</option>
                            @foreach ($caseTypes as $type)
                                <option value="{{ $type->value }}" {{ old('case_type') == $type->value ? 'selected' : '' }}>{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="cc-form-group">
                        <label for="court_name">Court Name</label>
                        <input type="text" id="court_name" name="court_name" value="{{ old('court_name') }}" placeholder="Enter court name" aria-label="Court name">
                    </div>
                </div>

                <div class="cc-form-row">
                    <div class="cc-form-group">
                        <label for="sla_deadline">
                            SLA Deadline
                            <span class="cc-required-asterisk" aria-hidden="true">*</span>
                        </label>
                        <input type="date" id="sla_deadline" name="sla_deadline" value="{{ old('sla_deadline') }}" required aria-required="true" aria-label="SLA deadline date">
                    </div>
                    <div class="cc-form-group">
                        <label for="asset_sla_in_days">
                            Asset SLA (in days)
                            <span class="cc-required-asterisk" aria-hidden="true">*</span>
                        </label>
                        <input type="number" id="asset_sla_in_days" name="asset_sla_in_days" value="{{ old('asset_sla_in_days') }}" placeholder="Enter number of days" min="0" step="1" required aria-required="true" aria-label="Asset SLA in days">
                    </div>
                    <div class="cc-form-group">
                        <label for="max_number_of_arbitation_per_user">
                            Max number of arbitration allowed per user
                            <span class="cc-required-asterisk" aria-hidden="true">*</span>
                        </label>
                        <input type="number" id="max_number_of_arbitation_per_user" name="max_number_of_arbitation_per_user" value="{{ old('max_number_of_arbitation_per_user', '0') }}" min="0" step="1" required aria-required="true" aria-label="Max number of arbitration allowed per user">
                    </div>
                </div>

                <div class="cc-form-group cc-form-group-full">
                    <label for="case_description">Case Description</label>
                    <textarea id="case_description" name="case_description" placeholder="Enter detailed case description..." aria-describedby="case_description-desc">{{ old('case_description') }}</textarea>
                    <span id="case_description-desc" class="sr-only">Optional description for the case.</span>
                </div>

                <div class="cc-section-divider" aria-hidden="true"></div>
                <span class="cc-section-title">Case Contact Information</span>
                <p class="cc-section-hint">Add one or more contacts. Search for an existing user by name or email, or enter new contact details below. Delegate role is not available here.</p>

                <div id="ccContactsWrapper" data-search-url="{{ url(route('admin.users.search')) }}">
                    @php
                        $contacts = old('contacts', [['email' => '', 'name' => '', 'phone' => '', 'role_id' => '', 'user_id' => '']]);
                        if (empty($contacts)) {
                            $contacts = [['email' => '', 'name' => '', 'phone' => '', 'role_id' => '', 'user_id' => '']];
                        }
                    @endphp
                    @foreach ($contacts as $idx => $c)
                    <div class="cc-contact-row" data-contact-index="{{ $idx }}">
                        <div class="cc-form-row">
                            <div class="cc-form-group cc-user-search-cell">
                                <label for="contacts_{{ $idx }}_user_search">Search user</label>
                                <div class="cc-typeahead-wrap">
                                    <input type="text" id="contacts_{{ $idx }}_user_search" class="cc-user-search-input" data-contact-index="{{ $idx }}" placeholder="Type name or email..." value="{{ !empty($c['user_id']) && isset($c['name']) ? ($c['name'] ?? '') . ' (' . ($c['email'] ?? '') . ')' : '' }}" autocomplete="off" aria-label="Search existing user by name or email">
                                    <div class="cc-typeahead-results" id="contacts_{{ $idx }}_results" role="listbox" aria-hidden="true"></div>
                                </div>
                                <input type="hidden" name="contacts[{{ $idx }}][user_id]" value="{{ $c['user_id'] ?? '' }}" class="cc-contact-user-id">
                            </div>
                            <div class="cc-form-group">
                                <label for="contacts_{{ $idx }}_email">
                                    Email
                                    <span class="cc-required-asterisk" aria-hidden="true">*</span>
                                </label>
                                <input type="email" id="contacts_{{ $idx }}_email" name="contacts[{{ $idx }}][email]" value="{{ $c['email'] ?? '' }}" placeholder="email@example.com" required aria-required="true" autocomplete="off">
                            </div>
                            <div class="cc-form-group">
                                <label for="contacts_{{ $idx }}_name">
                                    Name
                                    <span class="cc-required-asterisk" aria-hidden="true">*</span>
                                </label>
                                <input type="text" id="contacts_{{ $idx }}_name" name="contacts[{{ $idx }}][name]" value="{{ $c['name'] ?? '' }}" placeholder="Enter full name" required aria-required="true" autocomplete="off">
                            </div>
                            <div class="cc-form-group">
                                <label for="contacts_{{ $idx }}_phone">
                                    Phone
                                    <span class="cc-required-asterisk" aria-hidden="true">*</span>
                                </label>
                                <input type="tel" id="contacts_{{ $idx }}_phone" name="contacts[{{ $idx }}][phone]" value="{{ $c['phone'] ?? '' }}" placeholder="(123) 456-7890" required aria-required="true" inputmode="tel">
                            </div>
                            <div class="cc-form-group cc-contact-role-cell">
                                <label for="contacts_{{ $idx }}_role_id">
                                    Role
                                    <span class="cc-required-asterisk" aria-hidden="true">*</span>
                                </label>
                                <select id="contacts_{{ $idx }}_role_id" name="contacts[{{ $idx }}][role_id]" required aria-required="true">
                                    <option value="">Select Role</option>
                                    @foreach ($role as $item)
                                        <option value="{{ $item->value }}" {{ ($c['role_id'] ?? '') == $item->value ? 'selected' : '' }}>{{ $item->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="cc-form-group cc-contact-remove-cell">
                                <label class="cc-label-invisible">&nbsp;</label>
                                <button type="button" class="cc-btn-remove-contact btn-action-icon btn-delete" aria-label="Remove this contact" title="Remove this contact">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="cc-form-actions cc-add-contact-wrap">
                    <button type="button" id="ccAddContactBtn" class="cc-btn cc-btn-outline">+ Add contact</button>
                </div>

                <div class="cc-form-actions">
                    <button type="submit" class="cc-btn cc-btn-primary">Save Case</button>
                    <a href="{{ route('admin.cases.index') }}" class="cc-btn cc-btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script type="text/template" id="ccContactRowTemplate">
    <div class="cc-contact-row" data-contact-index="__INDEX__">
        <div class="cc-form-row">
            <div class="cc-form-group cc-user-search-cell">
                <label for="contacts___INDEX___user_search">Search user</label>
                <div class="cc-typeahead-wrap">
                    <input type="text" id="contacts___INDEX___user_search" class="cc-user-search-input" data-contact-index="__INDEX__" placeholder="Type name or email..." autocomplete="off" aria-label="Search existing user by name or email">
                    <div class="cc-typeahead-results" id="contacts___INDEX___results" role="listbox" aria-hidden="true"></div>
                </div>
                <input type="hidden" name="contacts[__INDEX__][user_id]" value="" class="cc-contact-user-id">
            </div>
            <div class="cc-form-group">
                <label for="contacts___INDEX___email">Email <span class="cc-required-asterisk" aria-hidden="true">*</span></label>
                <input type="email" id="contacts___INDEX___email" name="contacts[__INDEX__][email]" placeholder="email@example.com" required aria-required="true" autocomplete="off">
            </div>
            <div class="cc-form-group">
                <label for="contacts___INDEX___name">Name <span class="cc-required-asterisk" aria-hidden="true">*</span></label>
                <input type="text" id="contacts___INDEX___name" name="contacts[__INDEX__][name]" placeholder="Enter full name" required aria-required="true" autocomplete="off">
            </div>
            <div class="cc-form-group">
                <label for="contacts___INDEX___phone">Phone <span class="cc-required-asterisk" aria-hidden="true">*</span></label>
                <input type="tel" id="contacts___INDEX___phone" name="contacts[__INDEX__][phone]" placeholder="(123) 456-7890" required aria-required="true" inputmode="tel">
            </div>
            <div class="cc-form-group cc-contact-role-cell">
                <label for="contacts___INDEX___role_id">Role <span class="cc-required-asterisk" aria-hidden="true">*</span></label>
                <select id="contacts___INDEX___role_id" name="contacts[__INDEX__][role_id]" required aria-required="true">
                    <option value="">Select Role</option>
                    @foreach ($role as $item)
                        <option value="{{ $item->value }}">{{ $item->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="cc-form-group cc-contact-remove-cell cc-remove-wrap">
                <label class="cc-label-invisible">&nbsp;</label>
                <span class="cc-no-remove-hint" style="display:none" aria-hidden="true">—</span>
                <button type="button" class="cc-btn-remove-contact btn-action-icon btn-delete" aria-label="Remove this contact" title="Remove this contact">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                </button>
            </div>
        </div>
    </div>
</script>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var wrapper = document.getElementById('ccContactsWrapper');
    var addBtn = document.getElementById('ccAddContactBtn');
    var template = document.getElementById('ccContactRowTemplate');

    var PL = 'PL';
    var DEF = 'DEF';

    function applyPlaintiffDefendantLimits() {
        var selects = wrapper.querySelectorAll('select[name*="[role_id]"]');
        selects.forEach(function(sel) {
            var plOpt = sel.querySelector('option[value="' + PL + '"]');
            var defOpt = sel.querySelector('option[value="' + DEF + '"]');
            if (plOpt) plOpt.disabled = false;
            if (defOpt) defOpt.disabled = false;
        });
        var plSelectedIn = null;
        var defSelectedIn = null;
        selects.forEach(function(sel) {
            if (sel.value === PL) plSelectedIn = sel;
            if (sel.value === DEF) defSelectedIn = sel;
        });
        selects.forEach(function(sel) {
            var plOpt = sel.querySelector('option[value="' + PL + '"]');
            var defOpt = sel.querySelector('option[value="' + DEF + '"]');
            if (plOpt && plSelectedIn && plSelectedIn !== sel) plOpt.disabled = true;
            if (defOpt && defSelectedIn && defSelectedIn !== sel) defOpt.disabled = true;
        });
    }

    function nextIndex() {
        var rows = wrapper.querySelectorAll('.cc-contact-row');
        var max = -1;
        rows.forEach(function(r) {
            var idx = parseInt(r.getAttribute('data-contact-index'), 10);
            if (!isNaN(idx) && idx > max) max = idx;
        });
        return max + 1;
    }

    applyPlaintiffDefendantLimits();

    function updateRemoveButtonsCreate() {
        var LEGAL_RE = 'LEGAL_RE';
        var rows = wrapper.querySelectorAll('.cc-contact-row');
        var legalReCount = 0;
        rows.forEach(function(r) {
            var sel = r.querySelector('select[name*="[role_id]"]');
            if (sel && sel.value === LEGAL_RE) legalReCount++;
        });
        rows.forEach(function(row) {
            var sel = row.querySelector('select[name*="[role_id]"]');
            var role = sel ? sel.value : '';
            var removeCell = row.querySelector('.cc-remove-wrap') || row.querySelector('.cc-contact-remove-cell');
            if (!removeCell) return;
            var btn = removeCell.querySelector('.cc-btn-remove-contact');
            var hint = removeCell.querySelector('.cc-no-remove-hint');
            var cannotRemove = (role === LEGAL_RE && legalReCount <= 1);
            if (btn) {
                btn.style.display = cannotRemove ? 'none' : '';
                btn.disabled = cannotRemove;
            }
            if (hint) hint.style.display = cannotRemove ? '' : 'none';
        });
    }

    wrapper.addEventListener('change', function(e) {
        if (e.target && e.target.matches('select[name*="[role_id]"]')) {
            applyPlaintiffDefendantLimits();
            updateRemoveButtonsCreate();
        }
    });
    updateRemoveButtonsCreate();

    var searchUrl = wrapper.getAttribute('data-search-url') || '';
    var typeaheadTimeouts = {};

    function onUserSearchInput(inputEl) {
        var row = inputEl.closest('.cc-contact-row');
        if (!row) return;
        var idx = inputEl.getAttribute('data-contact-index');
        var resultsId = 'contacts_' + idx + '_results';
        var resultsEl = document.getElementById(resultsId) || row.querySelector('.cc-typeahead-results');
        var userIdInput = row.querySelector('.cc-contact-user-id');
        var emailInput = row.querySelector('input[name*="[email]"]');
        var nameInput = row.querySelector('input[name*="[name]"]');
        var phoneInput = row.querySelector('input[name*="[phone]"]');
        var val = (inputEl.value || '').trim();

        if (val.length < 2) {
            if (resultsEl) { resultsEl.innerHTML = ''; resultsEl.setAttribute('aria-hidden', 'true'); }
            if (val === '' && userIdInput && userIdInput.value) {
                userIdInput.value = '';
                if (emailInput) emailInput.value = '';
                if (nameInput) nameInput.value = '';
                if (phoneInput) phoneInput.value = '';
            }
            return;
        }

        clearTimeout(typeaheadTimeouts[idx]);
        typeaheadTimeouts[idx] = setTimeout(function() {
            fetch(searchUrl + '?q=' + encodeURIComponent(val), { credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function(r) { if (!r.ok) throw new Error('Search failed'); return r.json(); })
                .then(function(users) {
                    if (!resultsEl) return;
                    resultsEl.innerHTML = '';
                    resultsEl.setAttribute('aria-hidden', 'false');
                    if (users.length === 0) {
                        resultsEl.innerHTML = '<div class="cc-typeahead-item cc-typeahead-empty">No users found</div>';
                        return;
                    }
                    users.forEach(function(u) {
                        var div = document.createElement('div');
                        div.className = 'cc-typeahead-item';
                        div.setAttribute('role', 'option');
                        div.textContent = u.name + ' (' + (u.email || '') + ')';
                        div.dataset.id = u.id;
                        div.dataset.name = u.name || '';
                        div.dataset.email = u.email || '';
                        div.dataset.phone = (u.phone_number != null) ? u.phone_number : '';
                        div.addEventListener('click', function() {
                            var id = this.dataset.id;
                            var name = this.dataset.name;
                            var email = this.dataset.email;
                            var phone = this.dataset.phone;
                            if (userIdInput) userIdInput.value = id;
                            if (emailInput) emailInput.value = email;
                            if (nameInput) nameInput.value = name;
                            if (phoneInput) phoneInput.value = phone;
                            inputEl.value = name + ' (' + email + ')';
                            resultsEl.innerHTML = '';
                            resultsEl.setAttribute('aria-hidden', 'true');
                        });
                        resultsEl.appendChild(div);
                    });
                })
                .catch(function() {
                    if (resultsEl) { resultsEl.innerHTML = '<div class="cc-typeahead-item cc-typeahead-empty">Search failed</div>'; resultsEl.setAttribute('aria-hidden', 'false'); }
                });
        }, 300);
    }

    wrapper.addEventListener('input', function(e) {
        if (e.target && e.target.classList.contains('cc-user-search-input')) {
            onUserSearchInput(e.target);
        }
    });
    wrapper.addEventListener('focusin', function(e) {
        if (e.target && e.target.classList.contains('cc-user-search-input')) {
            var row = e.target.closest('.cc-contact-row');
            var res = row && row.querySelector('.cc-typeahead-results');
            if (res && res.children.length) res.setAttribute('aria-hidden', 'false');
        }
    });
    wrapper.addEventListener('focusout', function(e) {
        if (e.target && e.target.classList.contains('cc-user-search-input')) {
            var row = e.target.closest('.cc-contact-row');
            var res = row && row.querySelector('.cc-typeahead-results');
            if (res) {
                setTimeout(function() {
                    res.innerHTML = '';
                    res.setAttribute('aria-hidden', 'true');
                }, 200);
            }
        }
    });

    if (addBtn && template) {
        addBtn.addEventListener('click', function() {
            var idx = nextIndex();
            var html = template.innerHTML.replace(/__INDEX__/g, idx);
            var div = document.createElement('div');
            div.innerHTML = html.trim();
            wrapper.appendChild(div.firstChild);
            applyPlaintiffDefendantLimits();
            updateRemoveButtonsCreate();
        });
    }

    wrapper.addEventListener('click', function(e) {
        var btn = e.target && e.target.closest('.cc-btn-remove-contact');
        if (!btn || btn.disabled) return;
        var row = btn.closest('.cc-contact-row');
        if (!row) return;
        var rows = wrapper.querySelectorAll('.cc-contact-row');
        if (rows.length <= 1) return;
        row.remove();
        applyPlaintiffDefendantLimits();
        updateRemoveButtonsCreate();
    });
});
</script>
@endpush
@endsection

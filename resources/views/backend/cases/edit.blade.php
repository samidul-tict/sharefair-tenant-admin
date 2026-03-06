@extends('backend.layout.inner-app')
@section('title', 'Update Case | Share Fair')
@section('proxima')

<div class="case-create-modern">
    <div class="cc-page-container">
        <header class="cc-page-header">
            <nav class="cc-breadcrumb" aria-label="Breadcrumb">
                <a href="{{ route('admin.dashboard') }}">Dashboard</a>
                <span class="cc-breadcrumb-sep" aria-hidden="true">/</span>
                <a href="{{ route('admin.cases.index') }}">Cases</a>
                <span class="cc-breadcrumb-sep" aria-hidden="true">/</span>
                <span class="cc-breadcrumb-current">Edit Case</span>
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

            <form action="{{ route('admin.cases.update', $case->id) }}" method="POST" class="cc-form-grid" aria-label="Update case">
                @csrf
                @method('PUT')

                <div class="cc-form-row">
                    <div class="cc-form-group">
                        <label for="case_number">
                            Case Number
                            <span class="cc-required-asterisk" aria-hidden="true">*</span>
                        </label>
                        <input type="text" id="case_number" name="case_number" value="{{ old('case_number', $case->case_number) }}" required readonly aria-readonly="true">
                    </div>
                    <div class="cc-form-group">
                        <label for="case_type">
                            Case Type
                            <span class="cc-required-asterisk" aria-hidden="true">*</span>
                        </label>
                        <select id="case_type" name="case_type" required aria-required="true">
                            <option value="">Select Case Type</option>
                            @foreach ($caseType as $item)
                                <option value="{{ $item->value }}" {{ old('case_type', $case->case_type_value) == $item->value ? 'selected' : '' }}>{{ $item->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="cc-form-group">
                        <label for="court_name">Court Name</label>
                        <input type="text" id="court_name" name="court_name" value="{{ old('court_name', $case->court_name) }}" placeholder="Enter court name" aria-label="Court name">
                    </div>
                </div>

                <div class="cc-form-row">
                    <div class="cc-form-group">
                        <label for="sla_deadline">
                            SLA Deadline
                            <span class="cc-required-asterisk" aria-hidden="true">*</span>
                        </label>
                        <input type="date" id="sla_deadline" name="sla_deadline" value="{{ old('sla_deadline', $case->sla_deadline ? $case->sla_deadline->format('Y-m-d') : '') }}" required aria-required="true" aria-label="SLA deadline date">
                    </div>
                    <div class="cc-form-group">
                        <label for="asset_sla_in_days">
                            Asset SLA (in days)
                            <span class="cc-required-asterisk" aria-hidden="true">*</span>
                        </label>
                        <input type="number" id="asset_sla_in_days" name="asset_sla_in_days" value="{{ old('asset_sla_in_days', $case->asset_sla_in_days) }}" placeholder="Enter number of days" min="0" step="1" required aria-required="true" aria-label="Asset SLA in days">
                    </div>
                    <div class="cc-form-group">
                        <label for="max_number_of_arbitation_per_user">
                            Max number of arbitration allowed per user
                            <span class="cc-required-asterisk" aria-hidden="true">*</span>
                        </label>
                        <input type="number" id="max_number_of_arbitation_per_user" name="max_number_of_arbitation_per_user" value="{{ old('max_number_of_arbitation_per_user', $case->max_number_of_arbitation_per_user) }}" min="0" step="1" required aria-required="true" aria-label="Max number of arbitration allowed per user">
                    </div>
                </div>

                <div class="cc-form-group cc-form-group-full">
                    <label for="case_description">Case Description</label>
                    <textarea id="case_description" name="case_description" placeholder="Enter detailed case description..." aria-describedby="case_description-desc">{{ old('case_description', $case->case_description) }}</textarea>
                    <span id="case_description-desc" class="sr-only">Optional description for the case.</span>
                </div>

                <div class="cc-section-divider" aria-hidden="true"></div>
                <span class="cc-section-title">Legal Hold</span>
                <p class="cc-section-hint" id="legal-hold-desc">Enable legal hold and provide reason and date range.</p>

                <div class="cc-form-row cc-legal-hold-row">
                    <div class="cc-form-group">
                        <label class="cc-toggle-label">Legal Hold</label>
                        <div class="cc-toggle-wrap" role="group" aria-label="Legal hold on or off">
                            <input type="hidden" name="is_legal_hold" value="0">
                            <input type="checkbox" id="is_legal_hold" name="is_legal_hold" value="1" class="cc-toggle-input" {{ old('is_legal_hold', $case->is_legal_hold) ? 'checked' : '' }} aria-describedby="legal-hold-desc">
                            <label for="is_legal_hold" class="cc-toggle-slider" aria-hidden="true"></label>
                        </div>
                    </div>
                </div>
                <div id="legal_hold_fields" class="cc-legal-hold-fields" aria-hidden="true" style="display: none;">
                    <div class="cc-form-row">
                        <div class="cc-form-group cc-form-group-full">
                            <label for="legal_hold_reason">Reason</label>
                            <textarea id="legal_hold_reason" name="legal_hold_reason" placeholder="Enter legal hold reason..." rows="2" maxlength="4000" aria-label="Legal hold reason (4000 characters max)" aria-describedby="legal_hold_reason_count">{{ old('legal_hold_reason', $case->legal_hold_reason) }}</textarea>
                            <span id="legal_hold_reason_count" class="cc-char-count" aria-live="polite">0 / 4000</span>
                        </div>
                    </div>
                    <div class="cc-form-row">
                        <div class="cc-form-group">
                            <label for="legal_hold_start_date">Start Date</label>
                            <input type="date" id="legal_hold_start_date" name="legal_hold_start_date" value="{{ old('legal_hold_start_date', $case->legal_hold_start_date ? $case->legal_hold_start_date->format('Y-m-d') : '') }}" aria-label="Legal hold start date">
                        </div>
                        <div class="cc-form-group">
                            <label for="legal_hold_end_date">End Date</label>
                            <input type="date" id="legal_hold_end_date" name="legal_hold_end_date" value="{{ old('legal_hold_end_date', $case->legal_hold_end_date ? $case->legal_hold_end_date->format('Y-m-d') : '') }}" aria-label="Legal hold end date">
                        </div>
                    </div>
                </div>

                <div class="cc-section-divider" aria-hidden="true"></div>
                <span class="cc-section-title">Case Users</span>
                <p class="cc-section-hint" id="case-users-desc">Add or remove users. Search for an existing user by name or email, or enter details below. Only one Plaintiff and one Defendant allowed per case.</p>

                <div id="usersWrapper" data-search-url="{{ url(route('admin.users.search')) }}">
                    @php
                        $rows = old('users', []);
                    @endphp
                    @if(count($rows) > 0)
                        @foreach($rows as $i => $r)
                            <div class="cc-contact-row user-row" data-row-index="{{ $i }}">
                                <div class="cc-form-row">
                                    <input type="hidden" name="users[{{ $i }}][mapping_id]" value="{{ $r['mapping_id'] ?? '' }}">
                                    <div class="cc-form-group cc-user-search-cell">
                                        <label for="users_{{ $i }}_user_search">Search user</label>
                                        <div class="cc-typeahead-wrap">
                                            <input type="text" id="users_{{ $i }}_user_search" class="cc-user-search-input" data-row-index="{{ $i }}" placeholder="Type name or email..." value="{{ !empty($r['user_id']) && isset($r['name']) ? ($r['name'] ?? '') . ' (' . ($r['email'] ?? '') . ')' : '' }}" autocomplete="off" aria-label="Search user by name or email">
                                            <div class="cc-typeahead-results" id="users_{{ $i }}_results" role="listbox" aria-hidden="true"></div>
                                        </div>
                                        <input type="hidden" name="users[{{ $i }}][user_id]" value="{{ $r['user_id'] ?? '' }}" class="cc-user-id-input">
                                    </div>
                                    <div class="cc-form-group">
                                        <label for="users_{{ $i }}_email">Email <span class="cc-required-asterisk" aria-hidden="true">*</span></label>
                                        <input type="email" id="users_{{ $i }}_email" name="users[{{ $i }}][email]" value="{{ $r['email'] ?? '' }}" placeholder="email@example.com" required>
                                    </div>
                                    <div class="cc-form-group">
                                        <label for="users_{{ $i }}_name">Name <span class="cc-required-asterisk" aria-hidden="true">*</span></label>
                                        <input type="text" id="users_{{ $i }}_name" name="users[{{ $i }}][name]" value="{{ $r['name'] ?? '' }}" placeholder="Enter full name" required>
                                    </div>
                                    <div class="cc-form-group">
                                        <label for="users_{{ $i }}_phone">Phone <span class="cc-required-asterisk" aria-hidden="true">*</span></label>
                                        <input type="tel" id="users_{{ $i }}_phone" name="users[{{ $i }}][phone]" value="{{ $r['phone'] ?? $r['phone_number'] ?? '' }}" placeholder="(123) 456-7890" required>
                                    </div>
                                    <div class="cc-form-group cc-contact-role-cell">
                                        <label for="users_{{ $i }}_role">Role <span class="cc-required-asterisk" aria-hidden="true">*</span></label>
                                        <select id="users_{{ $i }}_role" name="users[{{ $i }}][role]" required class="cc-role-select">
                                            <option value="">Select Role</option>
                                            @foreach ($role as $item)
                                                <option value="{{ $item->value }}" {{ (isset($r['role']) && $r['role'] == $item->value) ? 'selected' : '' }}>{{ $item->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="cc-form-group cc-contact-remove-cell cc-remove-wrap" data-role="{{ $r['role'] ?? '' }}">
                                        <label class="cc-label-invisible">&nbsp;</label>
                                        @if((isset($r['role']) && $r['role'] !== 'PL' && $r['role'] !== 'DEF'))
                                        <button type="button" class="cc-btn-remove-contact removeRowBtn btn-action-icon btn-delete" aria-label="Remove this user" title="Remove this user">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                                        </button>
                                        @else
                                        <span class="cc-no-remove-hint" aria-hidden="true">—</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        @foreach($caseUsers as $idx => $mapping)
                            @php $u = $mapping->user; $selectedRole = old("users.$idx.role", $mapping->role_value ?? ''); $isPlDef = in_array($selectedRole, ['PL', 'DEF']); @endphp
                            <div class="cc-contact-row user-row" data-row-index="{{ $idx }}">
                                <div class="cc-form-row">
                                    <input type="hidden" name="users[{{ $idx }}][mapping_id]" value="{{ $mapping->id }}">
                                    <div class="cc-form-group cc-user-search-cell">
                                        <label for="users_{{ $idx }}_user_search">Search user</label>
                                        <div class="cc-typeahead-wrap">
                                            <input type="text" id="users_{{ $idx }}_user_search" class="cc-user-search-input" data-row-index="{{ $idx }}" placeholder="Type name or email..." value="{{ $u ? $u->name . ' (' . ($u->email ?? '') . ')' : '' }}" autocomplete="off" aria-label="Search user by name or email">
                                            <div class="cc-typeahead-results" id="users_{{ $idx }}_results" role="listbox" aria-hidden="true"></div>
                                        </div>
                                        <input type="hidden" name="users[{{ $idx }}][user_id]" value="{{ $u ? $u->id : '' }}" class="cc-user-id-input">
                                    </div>
                                    <div class="cc-form-group">
                                        <label for="users_{{ $idx }}_email">Email <span class="cc-required-asterisk" aria-hidden="true">*</span></label>
                                        <input type="email" id="users_{{ $idx }}_email" name="users[{{ $idx }}][email]" value="{{ old("users.$idx.email", $u->email ?? '') }}" placeholder="email@example.com" required>
                                    </div>
                                    <div class="cc-form-group">
                                        <label for="users_{{ $idx }}_name">Name <span class="cc-required-asterisk" aria-hidden="true">*</span></label>
                                        <input type="text" id="users_{{ $idx }}_name" name="users[{{ $idx }}][name]" value="{{ old("users.$idx.name", $u->name ?? '') }}" placeholder="Enter full name" required>
                                    </div>
                                    <div class="cc-form-group">
                                        <label for="users_{{ $idx }}_phone">Phone <span class="cc-required-asterisk" aria-hidden="true">*</span></label>
                                        <input type="tel" id="users_{{ $idx }}_phone" name="users[{{ $idx }}][phone]" value="{{ old("users.$idx.phone", $u->phone_number ?? '') }}" placeholder="(123) 456-7890" required>
                                    </div>
                                    <div class="cc-form-group cc-contact-role-cell">
                                        <label for="users_{{ $idx }}_role">Role <span class="cc-required-asterisk" aria-hidden="true">*</span></label>
                                        <select id="users_{{ $idx }}_role" name="users[{{ $idx }}][role]" required class="cc-role-select">
                                            <option value="">Select Role</option>
                                            @foreach ($role as $rl)
                                                <option value="{{ $rl->value }}" {{ $selectedRole == $rl->value ? 'selected' : '' }}>{{ $rl->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="cc-form-group cc-contact-remove-cell cc-remove-wrap" data-role="{{ $selectedRole }}">
                                        <label class="cc-label-invisible">&nbsp;</label>
                                        @if(!$isPlDef)
                                        <button type="button" class="cc-btn-remove-contact removeRowBtn btn-action-icon btn-delete" aria-label="Remove this user" title="Remove this user">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                                        </button>
                                        @else
                                        <span class="cc-no-remove-hint" aria-hidden="true">—</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>

                <div class="cc-form-actions cc-add-contact-wrap">
                    <button type="button" id="addUserRowBtn" class="cc-btn cc-btn-outline" aria-describedby="case-users-desc">+ Add User</button>
                </div>

                <div class="cc-form-actions">
                    <button type="submit" class="cc-btn cc-btn-primary">Save Changes</button>
                    <a href="{{ route('admin.cases.index') }}" class="cc-btn cc-btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const wrapper = document.getElementById('usersWrapper');
    const addBtn = document.getElementById('addUserRowBtn');
    const PL = 'PL';
    const DEF = 'DEF';

    function toggleLegalHoldFields() {
        var toggle = document.getElementById('is_legal_hold');
        var fields = document.getElementById('legal_hold_fields');
        if (!toggle || !fields) return;
        var on = toggle.checked;
        fields.style.display = on ? '' : 'none';
        fields.setAttribute('aria-hidden', on ? 'false' : 'true');
    }
    toggleLegalHoldFields();
    var legalHoldToggle = document.getElementById('is_legal_hold');
    if (legalHoldToggle) legalHoldToggle.addEventListener('change', toggleLegalHoldFields);

    function updateLegalHoldReasonCount() {
        var el = document.getElementById('legal_hold_reason');
        var countEl = document.getElementById('legal_hold_reason_count');
        if (!el || !countEl) return;
        var len = (el.value || '').length;
        var max = 4000;
        countEl.textContent = len + ' / ' + max;
    }
    var reasonField = document.getElementById('legal_hold_reason');
    if (reasonField) {
        reasonField.addEventListener('input', updateLegalHoldReasonCount);
        reasonField.addEventListener('paste', function() { setTimeout(updateLegalHoldReasonCount, 0); });
        updateLegalHoldReasonCount();
    }

    function applyPlaintiffDefendantLimits() {
        const selects = wrapper.querySelectorAll('select[name*="[role]"]');
        selects.forEach(sel => {
            const plOpt = sel.querySelector('option[value="' + PL + '"]');
            const defOpt = sel.querySelector('option[value="' + DEF + '"]');
            if (plOpt) plOpt.disabled = false;
            if (defOpt) defOpt.disabled = false;
        });
        let plSelectedIn = null, defSelectedIn = null;
        selects.forEach(sel => {
            if (sel.value === PL) plSelectedIn = sel;
            if (sel.value === DEF) defSelectedIn = sel;
        });
        selects.forEach(sel => {
            const plOpt = sel.querySelector('option[value="' + PL + '"]');
            const defOpt = sel.querySelector('option[value="' + DEF + '"]');
            if (plOpt && plSelectedIn && plSelectedIn !== sel) plOpt.disabled = true;
            if (defOpt && defSelectedIn && defSelectedIn !== sel) defOpt.disabled = true;
        });
    }

    function nextIndex() {
        const rows = wrapper.querySelectorAll('.user-row');
        if (!rows.length) return 0;
        let max = -1;
        rows.forEach(r => {
            const idx = parseInt(r.getAttribute('data-row-index'), 10);
            if (!isNaN(idx) && idx > max) max = idx;
        });
        return max + 1;
    }

    applyPlaintiffDefendantLimits();
    if (typeof updateRemoveButtons === 'function') updateRemoveButtons();

    wrapper.addEventListener('change', function(e) {
        if (e.target && e.target.matches('select[name*="[role]"]')) {
            applyPlaintiffDefendantLimits();
            updateRemoveButtons();
        }
    });

    var searchUrl = wrapper.getAttribute('data-search-url') || '';
    var typeaheadTimeoutsEdit = {};

    function onEditUserSearchInput(inputEl) {
        var row = inputEl.closest('.user-row');
        if (!row) return;
        var idx = inputEl.getAttribute('data-row-index');
        var resultsEl = row.querySelector('.cc-typeahead-results');
        var userIdInput = row.querySelector('.cc-user-id-input');
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

        clearTimeout(typeaheadTimeoutsEdit[idx]);
        typeaheadTimeoutsEdit[idx] = setTimeout(function() {
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
                            if (userIdInput) userIdInput.value = this.dataset.id;
                            if (emailInput) emailInput.value = this.dataset.email;
                            if (nameInput) nameInput.value = this.dataset.name;
                            if (phoneInput) phoneInput.value = this.dataset.phone;
                            inputEl.value = this.dataset.name + ' (' + this.dataset.email + ')';
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
        if (e.target && e.target.classList.contains('cc-user-search-input')) onEditUserSearchInput(e.target);
    });
    wrapper.addEventListener('focusout', function(e) {
        if (e.target && e.target.classList.contains('cc-user-search-input')) {
            var row = e.target.closest('.user-row');
            var res = row && row.querySelector('.cc-typeahead-results');
            if (res) setTimeout(function() { res.innerHTML = ''; res.setAttribute('aria-hidden', 'true'); }, 200);
        }
    });

    if (addBtn) {
        addBtn.addEventListener('click', function() {
            const idx = nextIndex();
            const row = document.createElement('div');
            row.className = 'cc-contact-row user-row';
            row.setAttribute('data-row-index', idx);
            row.innerHTML = `
                <div class="cc-form-row">
                    <input type="hidden" name="users[${idx}][mapping_id]" value="">
                    <div class="cc-form-group cc-user-search-cell">
                        <label for="users_${idx}_user_search">Search user</label>
                        <div class="cc-typeahead-wrap">
                            <input type="text" id="users_${idx}_user_search" class="cc-user-search-input" data-row-index="${idx}" placeholder="Type name or email..." autocomplete="off" aria-label="Search user by name or email">
                            <div class="cc-typeahead-results" id="users_${idx}_results" role="listbox" aria-hidden="true"></div>
                        </div>
                        <input type="hidden" name="users[${idx}][user_id]" value="" class="cc-user-id-input">
                    </div>
                    <div class="cc-form-group">
                        <label for="users_${idx}_email">Email <span class="cc-required-asterisk" aria-hidden="true">*</span></label>
                        <input type="email" id="users_${idx}_email" name="users[${idx}][email]" placeholder="email@example.com" required>
                    </div>
                    <div class="cc-form-group">
                        <label for="users_${idx}_name">Name <span class="cc-required-asterisk" aria-hidden="true">*</span></label>
                        <input type="text" id="users_${idx}_name" name="users[${idx}][name]" placeholder="Enter full name" required>
                    </div>
                    <div class="cc-form-group">
                        <label for="users_${idx}_phone">Phone <span class="cc-required-asterisk" aria-hidden="true">*</span></label>
                        <input type="tel" id="users_${idx}_phone" name="users[${idx}][phone]" placeholder="(123) 456-7890" required>
                    </div>
                    <div class="cc-form-group cc-contact-role-cell">
                        <label for="users_${idx}_role">Role <span class="cc-required-asterisk" aria-hidden="true">*</span></label>
                        <select id="users_${idx}_role" name="users[${idx}][role]" required class="cc-role-select">
                            <option value="">Select Role</option>
                            @foreach($role as $rle)
                                <option value="{{ $rle->value }}">{{ $rle->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="cc-form-group cc-contact-remove-cell cc-remove-wrap" data-role="">
                        <label class="cc-label-invisible">&nbsp;</label>
                        <span class="cc-no-remove-hint" style="display:none" aria-hidden="true">—</span>
                        <button type="button" class="cc-btn-remove-contact removeRowBtn btn-action-icon btn-delete" aria-label="Remove this user" title="Remove this user">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                        </button>
                    </div>
                </div>
            `;
            wrapper.appendChild(row);
            applyPlaintiffDefendantLimits();
            updateRemoveButtons();
        });
    }

    function updateRemoveButtons() {
        const LEGAL_RE = 'LEGAL_RE';
        const rows = wrapper.querySelectorAll('.user-row');
        let legalReCount = 0;
        rows.forEach(function(r) {
            const sel = r.querySelector('select[name*="[role]"]');
            if (sel && sel.value === LEGAL_RE) legalReCount++;
        });
        rows.forEach(function(row) {
            const sel = row.querySelector('select[name*="[role]"]');
            const role = sel ? sel.value : '';
            const removeCell = row.querySelector('.cc-remove-wrap') || row.querySelector('.cc-contact-remove-cell');
            if (!removeCell) return;
            const btn = removeCell.querySelector('.cc-btn-remove-contact');
            const hint = removeCell.querySelector('.cc-no-remove-hint');
            const cannotRemove = role === 'PL' || role === 'DEF' || (role === LEGAL_RE && legalReCount <= 1);
            if (btn) {
                btn.style.display = cannotRemove ? 'none' : '';
                btn.disabled = cannotRemove;
            }
            if (hint) hint.style.display = cannotRemove ? '' : 'none';
            removeCell.setAttribute('data-role', role);
        });
    }
    updateRemoveButtons();

    wrapper.addEventListener('click', function(e) {
        var btn = e.target && (e.target.closest('.removeRowBtn') || e.target.closest('.cc-btn-remove-contact'));
        if (!btn || btn.disabled) return;
        const row = btn.closest('.user-row');
        if (!row) return;
        row.remove();
        applyPlaintiffDefendantLimits();
        updateRemoveButtons();
    });
});
</script>
@endpush
@endsection

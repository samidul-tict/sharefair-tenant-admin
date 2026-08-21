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
            <h1 class="cc-page-title">Edit Case {{ $case->case_number }}</h1>
        </header>

        @include('backend.cases.partials.flash-alerts')

        <div class="cc-form-container">
            @php
                $lockDistributionConfig = $caseEditLocks['distribution_config'] ?? false;
                $lockDistributionAttempts = $caseEditLocks['distribution_attempts'] ?? false;
                $legalHoldOnly = $caseEditLocks['legal_hold_only'] ?? false;
            @endphp

            @if($legalHoldOnly)
                <div class="cc-alert cc-alert-info cc-edit-lock-notice" role="status">
                    This case is closed. Only the Legal Hold section can be edited.
                </div>
            @elseif($lockDistributionConfig || $lockDistributionAttempts)
                <div class="cc-alert cc-alert-info cc-edit-lock-notice" role="status">
                    Some case settings are locked based on the current case status and cannot be changed.
                </div>
            @endif

            <div class="cc-required-notice" role="status">
                Fields marked with an asterisk are required
            </div>

            <form action="{{ route('admin.cases.update', $case->id) }}" method="POST" class="cc-form-grid" novalidate aria-label="Update case">
                @csrf
                @method('PUT')

                <div class="cc-form-row">
                    <div class="cc-form-group">
                        <label for="case_number">
                            Case Number
                            <span class="cc-required-asterisk" aria-hidden="true">*</span>
                        </label>
                        <input type="text" id="case_number" value="{{ old('case_number', $case->case_number) }}" disabled aria-disabled="true" aria-readonly="true" class="cc-field-locked @error('case_number') cc-is-invalid @enderror" aria-invalid="{{ $errors->has('case_number') ? 'true' : 'false' }}" @error('case_number') aria-describedby="case_number-locked-hint case_number-error" @enderror>
                        <p id="case_number-locked-hint" class="cc-section-hint cc-field-hint">Cannot be changed after the case is created.</p>
                        @error('case_number')
                            <div id="case_number-error" class="cc-field-error" role="alert">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="cc-form-row">
                    <div class="cc-form-group">
                        <label for="sla_deadline">
                            SLA Deadline
                            <span class="cc-required-asterisk" aria-hidden="true">*</span>
                        </label>
                        <input type="date" id="sla_deadline" value="{{ old('sla_deadline', $case->sla_deadline ? $case->sla_deadline->format('Y-m-d') : '') }}" disabled aria-disabled="true" aria-readonly="true" aria-label="SLA deadline date" class="cc-field-locked @error('sla_deadline') cc-is-invalid @enderror" aria-invalid="{{ $errors->has('sla_deadline') ? 'true' : 'false' }}" @error('sla_deadline') aria-describedby="sla_deadline-locked-hint sla_deadline-error" @enderror>
                        <p id="sla_deadline-locked-hint" class="cc-section-hint cc-field-hint">Cannot be changed after the case is created.</p>
                        @error('sla_deadline')
                            <div id="sla_deadline-error" class="cc-field-error" role="alert">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="cc-form-group">
                        <label for="asset_sla_in_days">
                            Asset SLA (in days)
                            <span class="cc-required-asterisk" aria-hidden="true">*</span>
                        </label>
                        <input type="number" id="asset_sla_in_days" name="asset_sla_in_days" value="{{ old('asset_sla_in_days', $case->asset_sla_in_days) }}" placeholder="Enter number of days" min="0" step="1" required aria-required="true" aria-label="Asset SLA in days" @if($lockDistributionConfig) disabled aria-disabled="true" class="cc-field-locked @error('asset_sla_in_days') cc-is-invalid @enderror" @else class="@error('asset_sla_in_days') cc-is-invalid @enderror" @endif aria-invalid="{{ $errors->has('asset_sla_in_days') ? 'true' : 'false' }}" @error('asset_sla_in_days') aria-describedby="asset_sla_in_days-error" @enderror>
                        @if($lockDistributionConfig)
                            <input type="hidden" name="asset_sla_in_days" value="{{ old('asset_sla_in_days', $case->asset_sla_in_days) }}">
                        @endif
                        @error('asset_sla_in_days')
                            <div id="asset_sla_in_days-error" class="cc-field-error" role="alert">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="cc-form-group">
                        <label for="max_number_of_arbitation_per_user">
                            Max number of arbitration allowed per user
                            <span class="cc-required-asterisk" aria-hidden="true">*</span>
                        </label>
                        <input type="number" id="max_number_of_arbitation_per_user" name="max_number_of_arbitation_per_user" value="{{ old('max_number_of_arbitation_per_user', $case->max_number_of_arbitation_per_user) }}" placeholder="Enter max arbitration allowed" min="0" step="1" required aria-required="true" aria-label="Max number of arbitration allowed per user" @if($lockDistributionConfig) disabled aria-disabled="true" class="cc-field-locked @error('max_number_of_arbitation_per_user') cc-is-invalid @enderror" @else class="@error('max_number_of_arbitation_per_user') cc-is-invalid @enderror" @endif aria-invalid="{{ $errors->has('max_number_of_arbitation_per_user') ? 'true' : 'false' }}" @error('max_number_of_arbitation_per_user') aria-describedby="max_number_of_arbitation_per_user-error" @enderror>
                        @if($lockDistributionConfig)
                            <input type="hidden" name="max_number_of_arbitation_per_user" value="{{ old('max_number_of_arbitation_per_user', $case->max_number_of_arbitation_per_user) }}">
                        @endif
                        @error('max_number_of_arbitation_per_user')
                            <div id="max_number_of_arbitation_per_user-error" class="cc-field-error" role="alert">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="cc-form-row">
                    <div class="cc-form-group">
                        <label for="distribution_sla_in_days">Distribution SLA (in days)</label>
                        <input type="number" id="distribution_sla_in_days" name="distribution_sla_in_days" value="{{ old('distribution_sla_in_days', $case->distribution_sla_in_days) }}" placeholder="Enter number of days" min="0" step="1" inputmode="numeric" aria-describedby="distribution_sla_in_days-desc{{ $errors->has('distribution_sla_in_days') ? ' distribution_sla_in_days-error' : '' }}" aria-label="Distribution SLA in days" @if($lockDistributionConfig) disabled aria-disabled="true" class="cc-field-locked @error('distribution_sla_in_days') cc-is-invalid @enderror" @else class="@error('distribution_sla_in_days') cc-is-invalid @enderror" @endif aria-invalid="{{ $errors->has('distribution_sla_in_days') ? 'true' : 'false' }}">
                        @if($lockDistributionConfig)
                            <input type="hidden" name="distribution_sla_in_days" value="{{ old('distribution_sla_in_days', $case->distribution_sla_in_days) }}">
                        @endif
                        <span id="distribution_sla_in_days-desc" class="sr-only">Optional.</span>
                        @error('distribution_sla_in_days')
                            <div id="distribution_sla_in_days-error" class="cc-field-error" role="alert">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="cc-form-group">
                        <label for="max_number_of_distribution_attempts">
                            Max number of distribution attempts
                            <span class="cc-required-asterisk" aria-hidden="true">*</span>
                        </label>
                        <input type="number" id="max_number_of_distribution_attempts" name="max_number_of_distribution_attempts" value="{{ old('max_number_of_distribution_attempts', $case->max_number_of_distribution_attempts) }}" placeholder="Enter max distribution attempts" min="0" step="1" inputmode="numeric" required aria-required="true" aria-label="Max number of distribution attempts" @if($lockDistributionAttempts) disabled aria-disabled="true" class="cc-field-locked @error('max_number_of_distribution_attempts') cc-is-invalid @enderror" @else class="@error('max_number_of_distribution_attempts') cc-is-invalid @enderror" @endif aria-invalid="{{ $errors->has('max_number_of_distribution_attempts') ? 'true' : 'false' }}" @error('max_number_of_distribution_attempts') aria-describedby="max_number_of_distribution_attempts-error" @enderror>
                        @if($lockDistributionAttempts)
                            <input type="hidden" name="max_number_of_distribution_attempts" value="{{ old('max_number_of_distribution_attempts', $case->max_number_of_distribution_attempts) }}">
                        @endif
                        @error('max_number_of_distribution_attempts')
                            <div id="max_number_of_distribution_attempts-error" class="cc-field-error" role="alert">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="cc-form-group">
                        <label for="distribution_method">
                            Preferred distribution method
                            <span class="cc-required-asterisk" aria-hidden="true">*</span>
                        </label>
                        <select id="distribution_method" name="distribution_method" required aria-required="true" aria-label="Preferred distribution method" aria-describedby="distribution_method_helper{{ $errors->has('distribution_method') ? ' distribution_method-error' : '' }}" @if($lockDistributionConfig) disabled aria-disabled="true" class="cc-field-locked @error('distribution_method') cc-is-invalid @enderror" @else class="@error('distribution_method') cc-is-invalid @enderror" @endif aria-invalid="{{ $errors->has('distribution_method') ? 'true' : 'false' }}">
                            <option value="">Select distribution method</option>
                            @foreach ($distributionMethods as $method)
                                <option value="{{ $method->value }}" data-helper-text="{{ e($method->helper_text ?? '') }}" {{ old('distribution_method', $case->distribution_method_value) == $method->value ? 'selected' : '' }}>{{ $method->name }}</option>
                            @endforeach
                        </select>
                        @if($lockDistributionConfig)
                            <input type="hidden" name="distribution_method" value="{{ old('distribution_method', $case->distribution_method_value) }}">
                        @endif
                        <p id="distribution_method_helper" class="cc-section-hint cc-field-hint" role="status" aria-live="polite" aria-hidden="true"></p>
                        @error('distribution_method')
                            <div id="distribution_method-error" class="cc-field-error" role="alert">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="cc-form-row">
                    <div class="cc-form-group cc-form-group-full{{ $errors->has('asset_distributed_by') ? ' cc-has-error' : '' }}">
                        <fieldset>
                            <legend>
                                Asset will be distributed by
                                <span class="cc-required-asterisk" aria-hidden="true">*</span>
                            </legend>
                            @php
                                $assetDistributedBy = old('asset_distributed_by', ($case->distribute_by_client ?? false) ? 'client' : 'legal_representative');
                            @endphp
                            <div class="cc-radio-group" role="radiogroup" aria-label="Asset will be distributed by" aria-invalid="{{ $errors->has('asset_distributed_by') ? 'true' : 'false' }}" @error('asset_distributed_by') aria-describedby="asset_distributed_by-error" @enderror>
                                <label class="cc-radio-label">
                                    <input type="radio" name="asset_distributed_by" value="client" {{ $assetDistributedBy === 'client' ? 'checked' : '' }} required aria-required="true" @if($lockDistributionConfig) disabled aria-disabled="true" @endif>
                                    Client
                                </label>
                                <label class="cc-radio-label">
                                    <input type="radio" name="asset_distributed_by" value="legal_representative" {{ $assetDistributedBy === 'legal_representative' ? 'checked' : '' }} required aria-required="true" @if($lockDistributionConfig) disabled aria-disabled="true" @endif>
                                    Legal Representative
                                </label>
                            </div>
                            @if($lockDistributionConfig)
                                <input type="hidden" name="asset_distributed_by" value="{{ $assetDistributedBy }}">
                            @endif
                            @error('asset_distributed_by')
                                <div id="asset_distributed_by-error" class="cc-field-error" role="alert">{{ $message }}</div>
                            @enderror
                        </fieldset>
                    </div>
                </div>

                <div class="cc-form-group cc-form-group-full">
                    <label for="case_description">Case Description</label>
                    <textarea id="case_description" name="case_description" placeholder="Enter detailed case description..." aria-describedby="case_description-desc{{ $errors->has('case_description') ? ' case_description-error' : '' }}" @if($legalHoldOnly) readonly aria-readonly="true" class="cc-field-locked @error('case_description') cc-is-invalid @enderror" @else class="@error('case_description') cc-is-invalid @enderror" @endif aria-invalid="{{ $errors->has('case_description') ? 'true' : 'false' }}">{{ old('case_description', $case->case_description) }}</textarea>
                    <span id="case_description-desc" class="sr-only">Optional description for the case.</span>
                    @error('case_description')
                        <div id="case_description-error" class="cc-field-error" role="alert">{{ $message }}</div>
                    @enderror
                </div>

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
                            <textarea id="legal_hold_reason" name="legal_hold_reason" placeholder="Enter legal hold reason..." rows="2" maxlength="4000" aria-label="Legal hold reason (4000 characters max)" aria-describedby="legal_hold_reason_count{{ $errors->has('legal_hold_reason') ? ' legal_hold_reason-error' : '' }}" class="@error('legal_hold_reason') cc-is-invalid @enderror" aria-invalid="{{ $errors->has('legal_hold_reason') ? 'true' : 'false' }}">{{ old('legal_hold_reason', $case->legal_hold_reason) }}</textarea>
                            <span id="legal_hold_reason_count" class="cc-char-count" aria-live="polite">0 / 4000</span>
                            @error('legal_hold_reason')
                                <div id="legal_hold_reason-error" class="cc-field-error" role="alert">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="cc-form-row">
                        <div class="cc-form-group">
                            <label for="legal_hold_start_date">Start Date</label>
                            <input type="date" id="legal_hold_start_date" name="legal_hold_start_date" value="{{ old('legal_hold_start_date', $case->legal_hold_start_date ? $case->legal_hold_start_date->format('Y-m-d') : '') }}" aria-label="Legal hold start date" class="@error('legal_hold_start_date') cc-is-invalid @enderror" aria-invalid="{{ $errors->has('legal_hold_start_date') ? 'true' : 'false' }}" @error('legal_hold_start_date') aria-describedby="legal_hold_start_date-error" @enderror>
                            @error('legal_hold_start_date')
                                <div id="legal_hold_start_date-error" class="cc-field-error" role="alert">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="cc-form-group">
                            <label for="legal_hold_end_date">End Date</label>
                            <input type="date" id="legal_hold_end_date" name="legal_hold_end_date" value="{{ old('legal_hold_end_date', $case->legal_hold_end_date ? $case->legal_hold_end_date->format('Y-m-d') : '') }}" aria-label="Legal hold end date" class="@error('legal_hold_end_date') cc-is-invalid @enderror" aria-invalid="{{ $errors->has('legal_hold_end_date') ? 'true' : 'false' }}" @error('legal_hold_end_date') aria-describedby="legal_hold_end_date-error" @enderror>
                            @error('legal_hold_end_date')
                                <div id="legal_hold_end_date-error" class="cc-field-error" role="alert">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                @if(!$legalHoldOnly)
                @include('backend.cases.partials.case-parties-form', [
                    'fieldPrefix' => 'users',
                    'roleField' => 'role',
                    'partySlots' => $partySlots,
                    'additionalContacts' => $additionalContacts,
                    'counselEditable' => !($caseEditLocks['legal_hold_only'] ?? false),
                    'searchUrl' => url(route('admin.users.search')),
                    'sectionTitle' => 'Case Parties & Counsel',
                    'sectionHint' => 'Update each party and their counsel as they would appear in a legal filing. Saved Client and Spouse records cannot be removed or have their identity changed. Counsel for the Client and Spouse may be changed at any time until the case is resolved (RES_COMP). Additional legal representatives can be added or removed.',
                ])
                @endif

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
});

</script>
<script src="{{ asset('backend-assets/js/case-distribution-method-helper.js') }}"></script>
@endpush
@endsection

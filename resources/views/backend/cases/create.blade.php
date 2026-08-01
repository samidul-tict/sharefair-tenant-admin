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
            <h1 class="cc-page-title">Create Case</h1>
        </header>

        @include('backend.cases.partials.flash-alerts')

        <div class="cc-form-container">
            <div class="cc-required-notice" role="status">
                Fields marked with an asterisk are required
            </div>

            <form method="POST" action="{{ route('admin.cases.store') }}" class="cc-form-grid" novalidate aria-label="Create new case">
                @csrf

                <div class="cc-form-row">
                    <div class="cc-form-group">
                        <label for="case_number">
                            Case Number
                            <span class="cc-required-asterisk" aria-hidden="true">*</span>
                        </label>
                        <input type="text" id="case_number" name="case_number" value="{{ old('case_number') }}" placeholder="Enter case number" required aria-required="true" class="@error('case_number') cc-is-invalid @enderror" aria-invalid="{{ $errors->has('case_number') ? 'true' : 'false' }}" @error('case_number') aria-describedby="case_number-error" @enderror>
                        @error('case_number')
                            <div id="case_number-error" class="cc-field-error" role="alert">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="cc-form-group">
                        <label for="case_type">
                            Case Type
                            <span class="cc-required-asterisk" aria-hidden="true">*</span>
                        </label>
                        <select id="case_type" name="case_type" required aria-required="true" class="@error('case_type') cc-is-invalid @enderror" aria-invalid="{{ $errors->has('case_type') ? 'true' : 'false' }}" @error('case_type') aria-describedby="case_type-error" @enderror>
                            <option value="">Select Case Type</option>
                            @foreach ($caseTypes as $type)
                                <option value="{{ $type->value }}" {{ old('case_type') == $type->value ? 'selected' : '' }}>{{ $type->name }}</option>
                            @endforeach
                        </select>
                        @error('case_type')
                            <div id="case_type-error" class="cc-field-error" role="alert">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="cc-form-group">
                        <label for="court_name">Court Name</label>
                        <input type="text" id="court_name" name="court_name" value="{{ old('court_name') }}" placeholder="Enter court name" aria-label="Court name" class="@error('court_name') cc-is-invalid @enderror" aria-invalid="{{ $errors->has('court_name') ? 'true' : 'false' }}" @error('court_name') aria-describedby="court_name-error" @enderror>
                        @error('court_name')
                            <div id="court_name-error" class="cc-field-error" role="alert">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="cc-form-row">
                    <div class="cc-form-group">
                        <label for="sla_deadline">
                            SLA Deadline
                            <span class="cc-required-asterisk" aria-hidden="true">*</span>
                        </label>
                        <input type="date" id="sla_deadline" name="sla_deadline" value="{{ old('sla_deadline') }}" required aria-required="true" aria-label="SLA deadline date" class="@error('sla_deadline') cc-is-invalid @enderror" aria-invalid="{{ $errors->has('sla_deadline') ? 'true' : 'false' }}" @error('sla_deadline') aria-describedby="sla_deadline-error" @enderror>
                        @error('sla_deadline')
                            <div id="sla_deadline-error" class="cc-field-error" role="alert">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="cc-form-group">
                        <label for="asset_sla_in_days">
                            Asset SLA (in days)
                            <span class="cc-required-asterisk" aria-hidden="true">*</span>
                        </label>
                        <input type="number" id="asset_sla_in_days" name="asset_sla_in_days" value="{{ old('asset_sla_in_days') }}" placeholder="Enter number of days" min="0" step="1" required aria-required="true" aria-label="Asset SLA in days" class="@error('asset_sla_in_days') cc-is-invalid @enderror" aria-invalid="{{ $errors->has('asset_sla_in_days') ? 'true' : 'false' }}" @error('asset_sla_in_days') aria-describedby="asset_sla_in_days-error" @enderror>
                        @error('asset_sla_in_days')
                            <div id="asset_sla_in_days-error" class="cc-field-error" role="alert">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="cc-form-group">
                        <label for="max_number_of_arbitation_per_user">
                            Max number of arbitration allowed per user
                            <span class="cc-required-asterisk" aria-hidden="true">*</span>
                        </label>
                        <input type="number" id="max_number_of_arbitation_per_user" name="max_number_of_arbitation_per_user" value="{{ old('max_number_of_arbitation_per_user') }}" placeholder="Enter max arbitration allowed" min="0" step="1" required aria-required="true" aria-label="Max number of arbitration allowed per user" class="@error('max_number_of_arbitation_per_user') cc-is-invalid @enderror" aria-invalid="{{ $errors->has('max_number_of_arbitation_per_user') ? 'true' : 'false' }}" @error('max_number_of_arbitation_per_user') aria-describedby="max_number_of_arbitation_per_user-error" @enderror>
                        @error('max_number_of_arbitation_per_user')
                            <div id="max_number_of_arbitation_per_user-error" class="cc-field-error" role="alert">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="cc-form-row">
                    <div class="cc-form-group">
                        <label for="distribution_sla_in_days">Distribution SLA (in days)</label>
                        <input type="number" id="distribution_sla_in_days" name="distribution_sla_in_days" value="{{ old('distribution_sla_in_days') }}" placeholder="Enter number of days" min="0" step="1" inputmode="numeric" aria-describedby="distribution_sla_in_days-desc{{ $errors->has('distribution_sla_in_days') ? ' distribution_sla_in_days-error' : '' }}" aria-label="Distribution SLA in days" class="@error('distribution_sla_in_days') cc-is-invalid @enderror" aria-invalid="{{ $errors->has('distribution_sla_in_days') ? 'true' : 'false' }}">
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
                        <input type="number" id="max_number_of_distribution_attempts" name="max_number_of_distribution_attempts" value="{{ old('max_number_of_distribution_attempts') }}" placeholder="Enter max distribution attempts" min="0" step="1" inputmode="numeric" required aria-required="true" aria-label="Max number of distribution attempts" class="@error('max_number_of_distribution_attempts') cc-is-invalid @enderror" aria-invalid="{{ $errors->has('max_number_of_distribution_attempts') ? 'true' : 'false' }}" @error('max_number_of_distribution_attempts') aria-describedby="max_number_of_distribution_attempts-error" @enderror>
                        @error('max_number_of_distribution_attempts')
                            <div id="max_number_of_distribution_attempts-error" class="cc-field-error" role="alert">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="cc-form-group">
                        <label for="distribution_method">
                            Preferred distribution method
                            <span class="cc-required-asterisk" aria-hidden="true">*</span>
                        </label>
                        <select id="distribution_method" name="distribution_method" required aria-required="true" aria-label="Preferred distribution method" aria-describedby="distribution_method_helper{{ $errors->has('distribution_method') ? ' distribution_method-error' : '' }}" class="@error('distribution_method') cc-is-invalid @enderror" aria-invalid="{{ $errors->has('distribution_method') ? 'true' : 'false' }}">
                            <option value="">Select distribution method</option>
                            @foreach ($distributionMethods as $method)
                                <option value="{{ $method->value }}" data-helper-text="{{ e($method->helper_text ?? '') }}" {{ old('distribution_method') == $method->value ? 'selected' : '' }}>{{ $method->name }}</option>
                            @endforeach
                        </select>
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
                            <div class="cc-radio-group" role="radiogroup" aria-label="Asset will be distributed by" aria-invalid="{{ $errors->has('asset_distributed_by') ? 'true' : 'false' }}" @error('asset_distributed_by') aria-describedby="asset_distributed_by-error" @enderror>
                                <label class="cc-radio-label">
                                    <input type="radio" name="asset_distributed_by" value="client" {{ old('asset_distributed_by', 'legal_representative') === 'client' ? 'checked' : '' }} required aria-required="true">
                                    Client
                                </label>
                                <label class="cc-radio-label">
                                    <input type="radio" name="asset_distributed_by" value="legal_representative" {{ old('asset_distributed_by', 'legal_representative') === 'legal_representative' ? 'checked' : '' }} required aria-required="true">
                                    Legal Representative
                                </label>
                            </div>
                            @error('asset_distributed_by')
                                <div id="asset_distributed_by-error" class="cc-field-error" role="alert">{{ $message }}</div>
                            @enderror
                        </fieldset>
                    </div>
                </div>

                <div class="cc-form-group cc-form-group-full">
                    <label for="case_description">Case Description</label>
                    <textarea id="case_description" name="case_description" placeholder="Enter detailed case description..." aria-describedby="case_description-desc{{ $errors->has('case_description') ? ' case_description-error' : '' }}" class="@error('case_description') cc-is-invalid @enderror" aria-invalid="{{ $errors->has('case_description') ? 'true' : 'false' }}">{{ old('case_description') }}</textarea>
                    <span id="case_description-desc" class="sr-only">Optional description for the case.</span>
                    @error('case_description')
                        <div id="case_description-error" class="cc-field-error" role="alert">{{ $message }}</div>
                    @enderror
                </div>

                @include('backend.cases.partials.case-parties-form', [
                    'fieldPrefix' => 'contacts',
                    'roleField' => 'role_id',
                    'partySlots' => $partySlots,
                    'additionalContacts' => $additionalContacts,
                    'counselEditable' => true,
                    'searchUrl' => url(route('admin.users.search')),
                    'sectionTitle' => 'Case Parties & Counsel',
                    'sectionHint' => 'Enter each party and their counsel as they would appear in a legal filing. The Client and Spouse are required, along with at least one attorney for the Client. Additional entries are recorded as legal representatives.',
                ])

                <div class="cc-form-actions">
                    <button type="submit" class="cc-btn cc-btn-primary">Save Case</button>
                    <a href="{{ route('admin.cases.index') }}" class="cc-btn cc-btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script src="{{ asset('backend-assets/js/case-distribution-method-helper.js') }}"></script>
@endpush
@endsection

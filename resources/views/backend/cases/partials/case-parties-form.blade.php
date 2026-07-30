@php
    $fieldPrefix = $fieldPrefix ?? 'contacts';
    $roleField = $roleField ?? ($fieldPrefix === 'contacts' ? 'role_id' : 'role');
    $partySlots = $partySlots ?? [];
    $additionalContacts = $additionalContacts ?? [];
    $counselEditable = $counselEditable ?? true;
    $searchUrl = $searchUrl ?? url(route('admin.users.search'));
    $nextIndex = 4;
@endphp

<div class="cc-section-error-wrap{{ $errors->has($fieldPrefix) ? ' cc-has-error' : '' }}">
    <span class="cc-section-title">{{ $sectionTitle ?? 'Case Parties & Counsel' }}</span>
    <p class="cc-section-hint" id="case-parties-desc">
        {{ $sectionHint ?? 'Enter each party and their counsel in the order used in legal filings. Client and Spouse are required. At least one attorney must be listed for the Client.' }}
    </p>
    @error($fieldPrefix)
        <div class="cc-section-error" role="alert">{{ $message }}</div>
    @enderror
</div>

<div id="casePartiesWrapper" class="cc-parties-wrapper" data-search-url="{{ $searchUrl }}" data-field-prefix="{{ $fieldPrefix }}" data-role-field="{{ $roleField }}">
    @include('backend.cases.partials.case-party-block', [
        'fieldPrefix' => $fieldPrefix,
        'roleField' => $roleField,
        'index' => 0,
        'blockType' => 'client',
        'blockTitle' => 'The Client',
        'blockSubtitle' => 'The party initiating or primary client in this matter.',
        'roleValue' => 'PL',
        'row' => $partySlots['client'] ?? [],
        'lockPartyIdentity' => !empty($partySlots['client']['mapping_id'] ?? null),
        'canRemove' => false,
    ])

    @include('backend.cases.partials.case-party-block', [
        'fieldPrefix' => $fieldPrefix,
        'roleField' => $roleField,
        'index' => 1,
        'blockType' => 'client_counsel',
        'blockTitle' => 'Counsel for the Client',
        'blockSubtitle' => 'The attorney representing the Client in this case.',
        'legalCaptionKey' => 'client_counsel',
        'legalCaptionDefault' => 'I, [Attorney name], representing [Client name].',
        'roleValue' => 'LEGAL_RE',
        'representsParty' => 'client',
        'row' => $partySlots['client_counsel'] ?? [],
        'lockCounselIdentity' => !$counselEditable,
        'canRemove' => false,
    ])

    @include('backend.cases.partials.case-party-block', [
        'fieldPrefix' => $fieldPrefix,
        'roleField' => $roleField,
        'index' => 2,
        'blockType' => 'spouse',
        'blockTitle' => 'The Spouse',
        'blockSubtitle' => 'The other party in this matter.',
        'roleValue' => 'DEF',
        'row' => $partySlots['spouse'] ?? [],
        'lockPartyIdentity' => !empty($partySlots['spouse']['mapping_id'] ?? null),
        'canRemove' => false,
    ])

    @include('backend.cases.partials.case-party-block', [
        'fieldPrefix' => $fieldPrefix,
        'roleField' => $roleField,
        'index' => 3,
        'blockType' => 'spouse_counsel',
        'blockTitle' => 'Counsel for the Spouse',
        'blockSubtitle' => 'Optional — complete only if the Spouse is represented by separate counsel.',
        'legalCaptionKey' => 'spouse_counsel',
        'legalCaptionDefault' => 'I, [Attorney name], representing [Spouse name].',
        'roleValue' => 'LEGAL_RE',
        'representsParty' => 'spouse',
        'row' => $partySlots['spouse_counsel'] ?? [],
        'optional' => true,
        'lockCounselIdentity' => !$counselEditable,
        'canRemove' => false,
    ])

    <div class="cc-party-additional-wrap">
        <h3 class="cc-party-additional-title">Additional legal representatives</h3>
        <p class="cc-section-hint cc-party-additional-hint">Add co-counsel or other attorneys involved in this case. Each is recorded as a legal representative.</p>
        <div id="casePartiesAdditional" class="cc-parties-additional-list">
            @foreach($additionalContacts as $i => $additionalRow)
                @php $additionalIndex = $nextIndex + $i; @endphp
                @include('backend.cases.partials.case-party-block', [
                    'fieldPrefix' => $fieldPrefix,
                    'roleField' => $roleField,
                    'index' => $additionalIndex,
                    'blockType' => 'additional',
                    'blockTitle' => 'Additional legal representative',
                    'roleValue' => 'LEGAL_RE',
                    'row' => $additionalRow,
                    'canRemove' => true,
                ])
            @endforeach
        </div>
    </div>
</div>

<div class="cc-form-actions cc-add-contact-wrap">
    <button type="button" id="casePartiesAddBtn" class="cc-btn cc-btn-outline" aria-describedby="case-parties-desc">+ Add legal representative</button>
</div>

@push('scripts')
<script src="{{ asset('backend-assets/js/case-parties-form.js') }}"></script>
@endpush

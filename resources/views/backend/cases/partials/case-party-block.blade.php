@php
    $fieldPrefix = $fieldPrefix ?? 'contacts';
    $row = $row ?? [];
    $mappingId = $row['mapping_id'] ?? '';
    $userId = $row['user_id'] ?? '';
    $name = $row['name'] ?? '';
    $email = $row['email'] ?? '';
    $phone = $row['phone'] ?? $row['phone_number'] ?? '';
    $distributionCap = $row['distribution_value_cap'] ?? '';
    $isLocked = ($lockPartyIdentity ?? false) || ($lockCounselIdentity ?? false);
    $canRemove = $canRemove ?? false;
    $isOptional = $optional ?? false;
    $errorPrefix = $fieldPrefix . '.' . $index;
    $searchPurpose = $searchPurpose ?? 'counsel';
    $isPartySearch = $searchPurpose === 'party';
    $searchLabel = $searchLabel ?? ($isPartySearch ? 'Search end client' : 'Search employee or tenant admin');
    $searchPlaceholder = $searchPlaceholder ?? 'Type name, email, or phone...';
    $searchDisplay = $userId && $name ? $name . ' (' . ($email ?: '') . ')' : '';
    $isPrototype = (string) $index === '__INDEX__';
    $isManualEntry = $isPartySearch
        && !$isLocked
        && ($userId === '' || $userId === null)
        && ($name !== '' || $email !== '' || $phone !== '');
    $identityReadonly = $isLocked || !$isManualEntry;
    $addNewLabel = $blockType === 'spouse' ? 'Add new spouse' : 'Add new client';
@endphp

<article
    class="cc-party-block cc-party-block-{{ $blockType }}"
    data-party-block="{{ $blockType }}"
    data-contact-index="{{ $index }}"
    data-search-purpose="{{ $searchPurpose }}"
    @if($isPartySearch) data-add-new-label="{{ $addNewLabel }}" @endif
    @if($isManualEntry) data-manual-entry="1" @endif
    @if($isLocked) data-lock-party="1" @endif
    @if(!empty($mappingId)) data-saved="1" @endif
    @if($isOptional) data-party-optional="1" @endif
>
    <header class="cc-party-block-header">
        <h3 class="cc-party-block-title">{{ $blockTitle }}</h3>
        @if(!empty($blockSubtitle))
            <p class="cc-party-block-subtitle">{{ $blockSubtitle }}</p>
        @endif
        @if(!empty($legalCaptionKey))
            <p class="cc-legal-caption" data-caption-for="{{ $legalCaptionKey }}" aria-live="polite">
                {{ $legalCaptionDefault ?? '' }}
            </p>
        @endif
    </header>

    <div class="cc-party-block-body">
        <fieldset class="cc-party-fieldset" @if($isPrototype) disabled @endif>
        @if(!empty($roleValue))
            <input type="hidden" name="{{ $fieldPrefix }}[{{ $index }}][{{ $roleField }}]" value="{{ $roleValue }}">
        @endif
        @if($mappingId !== '')
            <input type="hidden" name="{{ $fieldPrefix }}[{{ $index }}][mapping_id]" value="{{ $mappingId }}">
        @endif
        @if(!empty($representsParty))
            <input type="hidden" name="{{ $fieldPrefix }}[{{ $index }}][represents_party]" value="{{ $representsParty }}">
        @endif

        <div class="cc-form-row cc-party-fields-row">
            <div class="cc-form-group cc-user-search-cell">
                <label for="{{ $fieldPrefix }}_{{ $index }}_user_search">{{ $searchLabel }}</label>
                <div class="cc-typeahead-wrap">
                    <input
                        type="text"
                        id="{{ $fieldPrefix }}_{{ $index }}_user_search"
                        class="cc-user-search-input @if($isLocked) cc-field-locked @endif"
                        data-contact-index="{{ $index }}"
                        role="combobox"
                        aria-autocomplete="list"
                        aria-expanded="false"
                        aria-controls="{{ $fieldPrefix }}_{{ $index }}_results"
                        placeholder="{{ $searchPlaceholder }}"
                        value="{{ $searchDisplay }}"
                        autocomplete="off"
                        aria-label="{{ $searchLabel }} by name, email, or phone"
                        @if($isLocked) disabled aria-disabled="true" @endif
                    >
                    <div class="cc-typeahead-results" id="{{ $fieldPrefix }}_{{ $index }}_results" role="listbox" aria-hidden="true"></div>
                </div>
                @if($isPartySearch)
                    <p class="cc-manual-entry-hint" data-manual-entry-hint @unless($isManualEntry) hidden @endunless>
                        Adding a new {{ $blockType === 'spouse' ? 'spouse' : 'client' }}. Enter their full name, email, and phone below.
                    </p>
                @endif
                <input type="hidden" name="{{ $fieldPrefix }}[{{ $index }}][user_id]" value="{{ $userId }}" class="cc-user-id-input cc-contact-user-id">
            </div>
            <div class="cc-form-group">
                <label for="{{ $fieldPrefix }}_{{ $index }}_name">
                    Full name
                    @unless($isOptional)<span class="cc-required-asterisk" aria-hidden="true">*</span>@endunless
                </label>
                <input
                    type="text"
                    id="{{ $fieldPrefix }}_{{ $index }}_name"
                    name="{{ $fieldPrefix }}[{{ $index }}][name]"
                    value="{{ $name }}"
                    placeholder="{{ $identityReadonly ? 'Filled from selected user' : 'Enter full name' }}"
                    @unless($isOptional) required aria-required="true" @endunless
                    class="cc-party-name-input @if($identityReadonly) cc-field-locked @endif @error("{$errorPrefix}.name") cc-is-invalid @enderror"
                    @if($identityReadonly) readonly aria-readonly="true" @endif
                    aria-invalid="{{ $errors->has("{$errorPrefix}.name") ? 'true' : 'false' }}"
                >
                @error("{$errorPrefix}.name")
                    <div class="cc-field-error" role="alert">{{ $message }}</div>
                @enderror
            </div>
            <div class="cc-form-group">
                <label for="{{ $fieldPrefix }}_{{ $index }}_email">
                    Email
                    @unless($isOptional)<span class="cc-required-asterisk" aria-hidden="true">*</span>@endunless
                </label>
                <input
                    type="email"
                    id="{{ $fieldPrefix }}_{{ $index }}_email"
                    name="{{ $fieldPrefix }}[{{ $index }}][email]"
                    value="{{ $email }}"
                    placeholder="{{ $identityReadonly ? 'Filled from selected user' : 'email@example.com' }}"
                    @unless($isOptional) required aria-required="true" @endunless
                    autocomplete="off"
                    class="@if($identityReadonly) cc-field-locked @endif @error("{$errorPrefix}.email") cc-is-invalid @enderror"
                    @if($identityReadonly) readonly aria-readonly="true" @endif
                    aria-invalid="{{ $errors->has("{$errorPrefix}.email") ? 'true' : 'false' }}"
                >
                @error("{$errorPrefix}.email")
                    <div class="cc-field-error" role="alert">{{ $message }}</div>
                @enderror
            </div>
            <div class="cc-form-group">
                <label for="{{ $fieldPrefix }}_{{ $index }}_phone">
                    Phone
                    @unless($isOptional)<span class="cc-required-asterisk" aria-hidden="true">*</span>@endunless
                </label>
                <input
                    type="tel"
                    id="{{ $fieldPrefix }}_{{ $index }}_phone"
                    name="{{ $fieldPrefix }}[{{ $index }}][phone]"
                    value="{{ $phone }}"
                    placeholder="{{ $identityReadonly ? 'Filled from selected user' : '(123) 456-7890' }}"
                    @unless($isOptional) required aria-required="true" @endunless
                    inputmode="tel"
                    class="@if($identityReadonly) cc-field-locked @endif @error("{$errorPrefix}.phone") cc-is-invalid @enderror"
                    @if($identityReadonly) readonly aria-readonly="true" @endif
                    aria-invalid="{{ $errors->has("{$errorPrefix}.phone") ? 'true' : 'false' }}"
                >
                @error("{$errorPrefix}.phone")
                    <div class="cc-field-error" role="alert">{{ $message }}</div>
                @enderror
            </div>
            <div class="cc-form-group cc-distribution-cap-field" data-distribution-cap-field hidden>
                <label for="{{ $fieldPrefix }}_{{ $index }}_distribution_value_cap">
                    Distribution value cap
                    <span class="cc-required-asterisk" aria-hidden="true">*</span>
                </label>
                <input
                    type="number"
                    id="{{ $fieldPrefix }}_{{ $index }}_distribution_value_cap"
                    name="{{ $fieldPrefix }}[{{ $index }}][distribution_value_cap]"
                    value="{{ $distributionCap }}"
                    placeholder="Enter value cap"
                    min="0"
                    step="0.01"
                    inputmode="decimal"
                    class="@error("{$errorPrefix}.distribution_value_cap") cc-is-invalid @enderror"
                >
                @error("{$errorPrefix}.distribution_value_cap")
                    <div class="cc-field-error" role="alert">{{ $message }}</div>
                @enderror
            </div>
            @if($canRemove)
                <div class="cc-form-group cc-contact-remove-cell cc-remove-wrap">
                    <label class="cc-label-invisible">&nbsp;</label>
                    <button type="button" class="cc-btn-remove-contact btn-action-icon btn-delete" aria-label="Remove legal representative" title="Remove legal representative">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                    </button>
                </div>
            @endif
        </div>
        </fieldset>
    </div>
</article>

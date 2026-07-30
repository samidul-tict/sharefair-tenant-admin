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
    $searchDisplay = $userId && $name ? $name . ' (' . ($email ?: '') . ')' : '';
@endphp

<article
    class="cc-party-block cc-party-block-{{ $blockType }}"
    data-party-block="{{ $blockType }}"
    data-contact-index="{{ $index }}"
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
                <label for="{{ $fieldPrefix }}_{{ $index }}_user_search">Search user</label>
                <div class="cc-typeahead-wrap">
                    <input
                        type="text"
                        id="{{ $fieldPrefix }}_{{ $index }}_user_search"
                        class="cc-user-search-input @if($isLocked) cc-field-locked @endif"
                        data-contact-index="{{ $index }}"
                        placeholder="Type name or email..."
                        value="{{ $searchDisplay }}"
                        autocomplete="off"
                        aria-label="Search user by name or email"
                        @if($isLocked) disabled aria-disabled="true" @endif
                    >
                    <div class="cc-typeahead-results" id="{{ $fieldPrefix }}_{{ $index }}_results" role="listbox" aria-hidden="true"></div>
                </div>
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
                    placeholder="Enter full name"
                    @unless($isOptional) required aria-required="true" @endunless
                    class="cc-party-name-input @if($isLocked) cc-field-locked @endif @error("{$errorPrefix}.name") cc-is-invalid @enderror"
                    @if($isLocked) readonly aria-readonly="true" @endif
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
                    placeholder="email@example.com"
                    @unless($isOptional) required aria-required="true" @endunless
                    autocomplete="off"
                    class="@error("{$errorPrefix}.email") cc-is-invalid @enderror"
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
                    placeholder="(123) 456-7890"
                    @unless($isOptional) required aria-required="true" @endunless
                    inputmode="tel"
                    class="@error("{$errorPrefix}.phone") cc-is-invalid @enderror"
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
                    <button type="button" class="cc-btn-remove-contact btn-action-icon btn-delete" aria-label="Remove participant" title="Remove participant">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                    </button>
                </div>
            @endif
        </div>
    </div>
</article>

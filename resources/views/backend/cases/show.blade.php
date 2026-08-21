@extends('backend.layout.inner-app')
@section('title', 'Case Details | Share Fair')
@section('proxima')

@php
    $caseStatusName = $case->caseStatus?->name ?? ($case->case_status_value ?? 'N/A');
    $userCount = $case->caseUsers->count();
    $assetCount = (int) ($assetCount ?? 0);
    $locationCount = $locations->count();
    $activityCount = $activityCount ?? 0;

    $roleBadgeClass = function ($row) {
        if ($row->role_value === 'LEGAL_RE') {
            return 'cs-role-employee';
        }
        if ($row->role_value === 'DEF') {
            return 'cs-role-defendant';
        }
        if ($row->role_value === 'PL') {
            return 'cs-role-plaintiff';
        }

        $rn = strtolower($row->role_name ?? '');
        if (str_contains($rn, 'employee')) {
            return 'cs-role-employee';
        }
        if (str_contains($rn, 'defendant')) {
            return 'cs-role-defendant';
        }
        if (str_contains($rn, 'plaintiff')) {
            return 'cs-role-plaintiff';
        }
        return 'cs-role-other';
    };

    $caseRoleDisplayName = function ($row) {
        return match ($row->role_value ?? '') {
            'PL' => 'Client',
            'DEF' => 'Spouse',
            default => $row->role_name ?? 'Not Assigned',
        };
    };

    $userInitials = function ($name) {
        $parts = preg_split('/\s+/', trim($name ?? ''));
        $initials = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= strtoupper(substr($part, 0, 1));
        }
        return $initials ?: '?';
    };

    $notesText = trim($case->case_description ?? '');
    $notesWords = $notesText !== '' ? preg_split('/\s+/u', $notesText, -1, PREG_SPLIT_NO_EMPTY) : [];
    $notesWordLimit = 40;
    $notesNeedsExpand = count($notesWords) > $notesWordLimit;
    $notesPreview = $notesNeedsExpand
        ? implode(' ', array_slice($notesWords, 0, $notesWordLimit)) . '…'
        : $notesText;

    $itemLabel = function ($value) use ($itemDataElementLabels) {
        if ($value === null || $value === '') {
            return '—';
        }

        return $itemDataElementLabels[$value] ?? $value;
    };

    $itemMoney = function ($value) {
        return $value !== null && $value !== ''
            ? '$' . number_format((float) $value, 2)
            : '—';
    };

    $itemBool = function ($value) {
        if ($value === null) {
            return '—';
        }

        return $value ? 'Yes' : 'No';
    };

    $canEditCase = hasPermission('cases', 'edit');

    $showDistributionCaps = in_array($case->distribution_method_value, ['DIST_FCP', 'DIST_CAP'], true);
    $clientCapUser = $case->caseUsers->firstWhere('role_value', 'PL');
    $spouseCapUser = $case->caseUsers->firstWhere('role_value', 'DEF');

    $partyByUserId = $case->caseUsers
        ->whereIn('role_value', ['PL', 'DEF'])
        ->keyBy(fn ($partyRow) => (int) $partyRow->user_id)
        ->map(fn ($partyRow) => [
            'name' => $partyRow->user_name ?? 'N/A',
            'label' => $partyRow->role_value === 'PL' ? 'Client' : 'Spouse',
        ]);

    $legalRepresentsLabel = function ($row) use ($partyByUserId) {
        if (($row->role_value ?? '') !== 'LEGAL_RE' || empty($row->representing_to_user)) {
            return null;
        }

        $party = $partyByUserId->get((int) $row->representing_to_user);
        if (!$party) {
            return null;
        }

        return 'Representing ' . $party['name'] . ' (' . $party['label'] . ')';
    };
@endphp

<div class="case-show-modern">
    <div class="cs-container">
        @include('backend.cases.partials.flash-alerts', ['showDistributedFlash' => true])
        @if($canDistribute ?? false)
            <div class="cs-attention-banner cs-attention-banner-page" role="status">
                <i class="fas fa-balance-scale" aria-hidden="true"></i>
                <span>
                    <strong>This case is ready for distribution.</strong>
                    Parties have completed their steps. Review allocations and confirm division.
                    <a href="{{ route('admin.cases.distribute.review', $case->id) }}" class="cs-attention-banner-action">Distribute assets</a>
                </span>
            </div>
        @endif
        <nav class="cs-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route('admin.dashboard') }}">Dashboard</a>
            <span class="cs-breadcrumb-sep" aria-hidden="true">/</span>
            <a href="{{ route('admin.cases.index') }}">Cases</a>
            <span class="cs-breadcrumb-sep" aria-hidden="true">/</span>
            <span class="cs-breadcrumb-current">{{ $case->case_number }}</span>
        </nav>

        <div class="cs-header">
            <div class="cs-case-title">
                <h1 class="cs-case-number">{{ $case->case_number }}</h1>
                <div class="cs-case-status-badge">
                    <span class="cs-status-dot" aria-hidden="true"></span>
                    {{ $caseStatusName }}
                </div>
            </div>
            <div class="cs-header-actions">
                @if(($showDistributionSummary ?? false) && $canEditCase)
                <a href="{{ route('admin.cases.distribute.review', $case->id) }}" class="{{ ($canDistribute ?? false) ? 'cs-btn-primary cs-btn-distribute' : 'cs-btn-secondary cs-btn-distribute cs-btn-distribute-muted' }}">
                    <i class="fas fa-balance-scale" aria-hidden="true"></i>
                    {{ ($canDistribute ?? false) ? 'Distribute assets' : 'Distribution summary' }}
                </a>
                @endif
                @if($canCloseCase ?? false)
                <button type="button" class="cs-btn-primary cs-btn-close-case" data-case-close-open>
                    <i class="fas fa-check-circle" aria-hidden="true"></i> Close case
                </button>
                @endif
                @if($canEditCase)
                <a href="{{ route('admin.cases.edit', $case->id) }}" class="cs-btn-secondary cs-btn-edit">
                    <i class="fas fa-pen" aria-hidden="true"></i> Edit Case
                </a>
                @endif
                <a href="{{ route('admin.cases.index') }}" class="cs-btn-secondary">
                    <i class="fas fa-arrow-left" aria-hidden="true"></i> Back to cases
                </a>
            </div>
        </div>

        <div class="cs-tab-bar" role="tablist" aria-label="Case sections">
            <button type="button" class="cs-tab is-active" role="tab" id="tab-overview" aria-selected="true" aria-controls="panel-overview" data-tab="overview">
                <i class="fas fa-th-large" aria-hidden="true"></i> Overview
            </button>
            <button type="button" class="cs-tab" role="tab" id="tab-locations" aria-selected="false" aria-controls="panel-locations" data-tab="locations">
                <i class="fas fa-map-marker-alt" aria-hidden="true"></i> Locations
                <span class="cs-tab-count">{{ $locationCount }}</span>
            </button>
            <button type="button" class="cs-tab" role="tab" id="tab-users" aria-selected="false" aria-controls="panel-users" data-tab="users">
                <i class="fas fa-users" aria-hidden="true"></i> Participants
                <span class="cs-tab-count">{{ $userCount }}</span>
            </button>
            <button type="button" class="cs-tab" role="tab" id="tab-assets" aria-selected="false" aria-controls="panel-assets" data-tab="assets">
                <i class="fas fa-box" aria-hidden="true"></i> Assets
                <span class="cs-tab-count">{{ $assetCount }}</span>
            </button>
            <button type="button" class="cs-tab" role="tab" id="tab-comments" aria-selected="false" aria-controls="panel-comments" data-tab="comments">
                <i class="fas fa-comments" aria-hidden="true"></i> Comments
                <span class="cs-tab-count" id="caseCommentsTabCount">{{ $commentCount ?? 0 }}</span>
            </button>
            <button type="button" class="cs-tab" role="tab" id="tab-activities" aria-selected="false" aria-controls="panel-activities" data-tab="activities">
                <i class="fas fa-stream" aria-hidden="true"></i> Activities
                <span class="cs-tab-count">{{ $activityCount }}</span>
            </button>
        </div>

        {{-- Overview --}}
        <div class="cs-tab-panel is-active" role="tabpanel" id="panel-overview" aria-labelledby="tab-overview" data-panel="overview">
            <div class="cs-stat-grid">
                <button type="button" class="cs-stat-tile cs-stat-tile-assets" data-jump-tab="assets">
                    <i class="fas fa-box cs-stat-icon" aria-hidden="true"></i>
                    <div class="cs-stat-value">{{ $assetCount }}</div>
                    <div class="cs-stat-label">Assets</div>
                </button>
                <button type="button" class="cs-stat-tile cs-stat-tile-locations" data-jump-tab="locations">
                    <i class="fas fa-map-marker-alt cs-stat-icon" aria-hidden="true"></i>
                    <div class="cs-stat-value">{{ $locationCount }}</div>
                    <div class="cs-stat-label">Storage locations</div>
                </button>
                <button type="button" class="cs-stat-tile cs-stat-tile-users" data-jump-tab="users">
                    <i class="fas fa-users cs-stat-icon" aria-hidden="true"></i>
                    <div class="cs-stat-value">{{ $userCount }}</div>
                    <div class="cs-stat-label">Participants</div>
                </button>
            </div>

            <div class="cs-overview-grid">
                <div class="cs-panel">
                    <h2 class="cs-panel-title"><i class="fas fa-balance-scale" aria-hidden="true"></i> Matter details</h2>
                    <dl class="cs-kv-list">
                        <div class="cs-kv-row"><dt>Max arbitration per user</dt><dd>{{ $case->max_number_of_arbitation_per_user ?? '—' }}</dd></div>
                        <div class="cs-kv-row"><dt>Legal hold</dt><dd>{{ $case->is_legal_hold ? 'Yes' : 'No' }}</dd></div>
                        @if($case->is_legal_hold && $case->legal_hold_reason)
                        <div class="cs-kv-row"><dt>Hold reason</dt><dd>{{ $case->legal_hold_reason }}</dd></div>
                        @endif
                        <div class="cs-kv-row"><dt>Asset distributed by</dt><dd>{{ ($case->distribute_by_client ?? false) ? 'Client' : 'Legal Representative' }}</dd></div>
                        <div class="cs-kv-row"><dt>Division method</dt><dd>{{ $case->distributionMethod?->name ?? ($case->distribution_method_value ?: '—') }}</dd></div>
                        @if($showDistributionCaps)
                        <div class="cs-kv-row"><dt>Client value cap</dt><dd>{{ $itemMoney($clientCapUser->distribution_value_cap ?? null) }}</dd></div>
                        <div class="cs-kv-row"><dt>Spouse value cap</dt><dd>{{ $itemMoney($spouseCapUser->distribution_value_cap ?? null) }}</dd></div>
                        @endif
                        <div class="cs-kv-row"><dt>Max distribution attempts</dt><dd>{{ $case->max_number_of_distribution_attempts ?? 0 }}</dd></div>
                    </dl>
                </div>

                <div class="cs-panel">
                    <h2 class="cs-panel-title"><i class="fas fa-calendar-alt" aria-hidden="true"></i> Key dates</h2>
                    <dl class="cs-kv-list">
                        <div class="cs-kv-row"><dt>SLA deadline</dt><dd>{{ $case->sla_deadline ? $case->sla_deadline->format('M j, Y') : '—' }}</dd></div>
                        <div class="cs-kv-row"><dt>Asset SLA (days)</dt><dd>{{ $case->asset_sla_in_days ?? '—' }}</dd></div>
                        <div class="cs-kv-row"><dt>Distribution SLA (days)</dt><dd>{{ $case->distribution_sla_in_days ?? '—' }}</dd></div>
                        <div class="cs-kv-row"><dt>Opened on</dt><dd>{{ $case->created_date ? $case->created_date->format('M j, Y') : '—' }}</dd></div>
                        <div class="cs-kv-row"><dt>Opened by</dt><dd>{{ $case->createdBy?->name ?? '—' }}</dd></div>
                        <div class="cs-kv-row"><dt>Last updated</dt><dd>{{ $case->last_modified_date ? $case->last_modified_date->format('M j, Y') : '—' }}</dd></div>
                        @if($case->distribution_date)
                        <div class="cs-kv-row"><dt>Property divided on</dt><dd>{{ $case->distribution_date->format('M j, Y') }}</dd></div>
                        @endif
                        @if($case->is_legal_hold)
                        <div class="cs-kv-row"><dt>Hold started</dt><dd>{{ $case->legal_hold_start_date ? $case->legal_hold_start_date->format('M j, Y') : '—' }}</dd></div>
                        <div class="cs-kv-row"><dt>Hold ends</dt><dd>{{ $case->legal_hold_end_date ? $case->legal_hold_end_date->format('M j, Y') : '—' }}</dd></div>
                        @endif
                    </dl>
                </div>

                <div class="cs-panel">
                    <h2 class="cs-panel-title"><i class="fas fa-chart-pie" aria-hidden="true"></i> Property summary</h2>
                    <dl class="cs-kv-list">
                        <div class="cs-kv-row"><dt>Total assets</dt><dd>{{ $case->total_items_count ?? $assetCount }}</dd></div>
                        <div class="cs-kv-row"><dt>Total value</dt><dd>{{ $case->total_items_value !== null ? number_format((float) $case->total_items_value, 2) : '—' }}</dd></div>
                        <div class="cs-kv-row"><dt>Marital property (count)</dt><dd>{{ $case->total_marital_assets_count ?? '—' }}</dd></div>
                        <div class="cs-kv-row"><dt>Marital property value</dt><dd>{{ $case->total_marital_assets_value !== null ? number_format((float) $case->total_marital_assets_value, 2) : '—' }}</dd></div>
                        <div class="cs-kv-row"><dt>Separate property (count)</dt><dd>{{ $case->total_non_marital_assets_count ?? '—' }}</dd></div>
                        <div class="cs-kv-row"><dt>Declined items</dt><dd>{{ $case->total_dont_want_items_count ?? '—' }}</dd></div>
                        <div class="cs-kv-row"><dt>People in matter</dt><dd>{{ $participatingUserCount }}</dd></div>
                        <div class="cs-kv-row"><dt>Target share per person</dt><dd>{{ $case->target_value_per_user !== null ? number_format((float) $case->target_value_per_user, 2) : '—' }}</dd></div>
                    </dl>
                </div>

                <div class="cs-panel cs-panel-notes">
                    <h2 class="cs-panel-title"><i class="fas fa-sticky-note" aria-hidden="true"></i> Matter notes</h2>
                    @if($notesText !== '')
                        <div class="cs-notes-block" id="matterNotesBlock" data-expanded="false">
                            <p class="cs-notes-text" id="matterNotesText">{{ $notesPreview }}</p>
                            <p class="cs-notes-text cs-notes-full" id="matterNotesFull" hidden>{{ $notesText }}</p>
                            @if($notesNeedsExpand)
                                <button type="button" class="cs-notes-toggle" id="matterNotesToggle" aria-expanded="false" aria-controls="matterNotesText matterNotesFull">
                                    Read more
                                </button>
                            @endif
                        </div>
                    @else
                        <p class="cs-notes-empty">No notes provided.</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Locations --}}
        <div class="cs-tab-panel" role="tabpanel" id="panel-locations" aria-labelledby="tab-locations" data-panel="locations" hidden>
            <div class="cs-section-header">
                <h2 class="cs-section-title">Storage locations</h2>
                <p class="cs-section-subtitle">{{ $locationCount }} location{{ $locationCount === 1 ? '' : 's' }} linked to this case</p>
            </div>
            @if($locations->count() > 0)
                <div class="cs-location-grid">
                    @foreach($locations as $location)
                        <article class="cs-location-card">
                            <div class="cs-location-icon" aria-hidden="true">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="cs-location-body">
                                <h3 class="cs-location-name">{{ $location->name ?: 'Unnamed location' }}</h3>
                                <p class="cs-location-address">{{ $location->formattedAddress() ?: 'No address on file' }}</p>
                                @if($location->mapsUrl())
                                    <a href="{{ $location->mapsUrl() }}" class="cs-location-map-link" target="_blank" rel="noopener noreferrer">
                                        View on map <i class="fas fa-external-link-alt" aria-hidden="true"></i>
                                    </a>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="cs-empty-state">
                    <div class="cs-empty-icon" aria-hidden="true"><i class="fas fa-map-marker-alt"></i></div>
                    <div class="cs-empty-text">No storage locations found for this case.</div>
                </div>
            @endif
        </div>

        {{-- Users --}}
        <div class="cs-tab-panel" role="tabpanel" id="panel-users" aria-labelledby="tab-users" data-panel="users" hidden>
            <div class="cs-section-header">
                <h2 class="cs-section-title">Participants</h2>
                <p class="cs-section-subtitle">{{ $userCount }} user{{ $userCount === 1 ? '' : 's' }} assigned to this case</p>
            </div>
            <div id="caseUsersTableWrapper" role="region" aria-label="Case users" aria-live="polite">
                @if($case->caseUsers->count() > 0)
                    <div class="cs-user-grid">
                        @foreach($case->caseUsers as $row)
                            @php $representsLabel = $legalRepresentsLabel($row); @endphp
                            <article class="cs-user-card-v2" data-mapping-id="{{ $row->id }}">
                                <div class="cs-user-avatar" aria-hidden="true">{{ $userInitials($row->user_name) }}</div>
                                <div class="cs-user-body">
                                    <div class="cs-user-name">{{ $row->user_name ?? 'N/A' }}</div>
                                    @if($representsLabel)
                                        <p class="cs-user-represents">
                                            <i class="fas fa-briefcase" aria-hidden="true"></i>
                                            {{ $representsLabel }}
                                        </p>
                                    @endif
                                    <div class="cs-user-meta">
                                        <span><i class="fas fa-envelope" aria-hidden="true"></i> {{ $row->user_email ?? 'N/A' }}</span>
                                        <span><i class="fas fa-phone" aria-hidden="true"></i> {{ $row->user_phone ?? 'N/A' }}</span>
                                        @if($showDistributionCaps && in_array($row->role_value, ['PL', 'DEF'], true))
                                        <span><i class="fas fa-hand-holding-usd" aria-hidden="true"></i> Value cap: {{ $itemMoney($row->distribution_value_cap ?? null) }}</span>
                                        @endif
                                    </div>
                                    <span class="cs-role-badge {{ $roleBadgeClass($row) }}">{{ $caseRoleDisplayName($row) }}</span>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="cs-empty-state">
                        <div class="cs-empty-icon" aria-hidden="true"><i class="fas fa-users"></i></div>
                        <div class="cs-empty-text">No users assigned to this case.</div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Assets --}}
        <div class="cs-tab-panel" role="tabpanel" id="panel-assets" aria-labelledby="tab-assets" data-panel="assets" hidden>
            <div class="cs-section-header cs-assets-header">
                <div>
                    <h2 class="cs-section-title">Assets</h2>
                    <p class="cs-section-subtitle" id="assetsCountSubtitle">{{ $assetCount }} asset{{ $assetCount === 1 ? '' : 's' }} in this case</p>
                </div>
            </div>
            @if($assetCount > 0)
                <div class="cs-assets-toolbar" id="assetsToolbar">
                    <div class="cs-assets-toolbar-row">
                        <label class="cs-assets-search-wrap">
                            <span class="sr-only">Search assets</span>
                            <i class="fas fa-search cs-assets-search-icon" aria-hidden="true"></i>
                            <input type="search" id="assetsSearch" class="cs-assets-search" placeholder="Search across asset columns…" autocomplete="off">
                        </label>
                        <select id="assetsFilterStatus" class="cs-assets-select" aria-label="Filter by status">
                            <option value="">All statuses</option>
                        </select>
                        <select id="assetsFilterCategory" class="cs-assets-select" aria-label="Filter by category">
                            <option value="">All categories</option>
                        </select>
                        <select id="assetsFilterLocation" class="cs-assets-select" aria-label="Filter by location">
                            <option value="">All locations</option>
                        </select>
                        <select id="assetsPerPage" class="cs-assets-select cs-assets-per-page" aria-label="Rows per page">
                            <option value="25" selected>25 / page</option>
                            <option value="50">50 / page</option>
                            <option value="100">100 / page</option>
                        </select>
                        <button type="button" class="cs-assets-columns-btn" id="assetsColumnsBtn" aria-expanded="false" aria-controls="assetsColumnsPanel">
                            <i class="fas fa-columns" aria-hidden="true"></i> Columns
                        </button>
                    </div>

                    <div class="cs-assets-columns-panel" id="assetsColumnsPanel" hidden>
                        <p class="cs-assets-columns-title">Show and reorder columns</p>
                        <p class="cs-assets-columns-hint">Drag columns into your preferred order.</p>
                        <div class="cs-assets-columns-grid">
                            <label class="cs-assets-col-toggle"><input type="checkbox" data-col="index" checked disabled> #</label>
                            <label class="cs-assets-col-toggle"><input type="checkbox" data-col="name" checked disabled> Name</label>
                            <label class="cs-assets-col-toggle"><input type="checkbox" data-col="location" checked> Location</label>
                            <label class="cs-assets-col-toggle"><input type="checkbox" data-col="category" checked> Category</label>
                            <label class="cs-assets-col-toggle"><input type="checkbox" data-col="other_category"> Other category</label>
                            <label class="cs-assets-col-toggle"><input type="checkbox" data-col="condition" checked> Condition</label>
                            <label class="cs-assets-col-toggle"><input type="checkbox" data-col="brand" checked> Brand</label>
                            <label class="cs-assets-col-toggle"><input type="checkbox" data-col="other_brand"> Other brand</label>
                            <label class="cs-assets-col-toggle"><input type="checkbox" data-col="purchase_year"> Purchase year</label>
                            <label class="cs-assets-col-toggle"><input type="checkbox" data-col="purchase_price" checked> Purchase price</label>
                            <label class="cs-assets-col-toggle"><input type="checkbox" data-col="estimated_value" checked> Estimated value</label>
                            <label class="cs-assets-col-toggle"><input type="checkbox" data-col="concluded_price" checked> Concluded price</label>
                            <label class="cs-assets-col-toggle"><input type="checkbox" data-col="accessories_status"> Accessories status</label>
                            <label class="cs-assets-col-toggle"><input type="checkbox" data-col="original_packaging"> Original packaging</label>
                            <label class="cs-assets-col-toggle"><input type="checkbox" data-col="valid_warranty"> Valid warranty</label>
                            <label class="cs-assets-col-toggle"><input type="checkbox" data-col="marital_asset" checked> Marital asset</label>
                            <label class="cs-assets-col-toggle"><input type="checkbox" data-col="assigned_to" checked> Assigned to</label>
                            <label class="cs-assets-col-toggle"><input type="checkbox" data-col="assigned_reason"> Assigned reason</label>
                            <label class="cs-assets-col-toggle"><input type="checkbox" data-col="status" checked> Status</label>
                        </div>
                        <button type="button" class="cs-assets-columns-reset" id="assetsColumnsReset">Reset columns</button>
                    </div>
                </div>

                <div class="cs-table-container cs-assets-table-wrap">
                    <table class="cs-table cs-assets-table" id="assetsTable" role="table">
                        <caption class="sr-only">Assets in this case</caption>
                        <thead>
                            <tr>
                                <th scope="col" data-col="index">#</th>
                                <th scope="col" data-col="name">Name</th>
                                <th scope="col" data-col="location">Location</th>
                                <th scope="col" data-col="category">Category</th>
                                <th scope="col" data-col="other_category">Other category</th>
                                <th scope="col" data-col="condition">Condition</th>
                                <th scope="col" data-col="brand">Brand</th>
                                <th scope="col" data-col="other_brand">Other brand</th>
                                <th scope="col" data-col="purchase_year">Purchase year</th>
                                <th scope="col" data-col="purchase_price">Purchase price</th>
                                <th scope="col" data-col="estimated_value">Estimated value</th>
                                <th scope="col" data-col="concluded_price">Concluded price</th>
                                <th scope="col" data-col="accessories_status">Accessories status</th>
                                <th scope="col" data-col="original_packaging">Original packaging</th>
                                <th scope="col" data-col="valid_warranty">Valid warranty</th>
                                <th scope="col" data-col="marital_asset">Marital asset</th>
                                <th scope="col" data-col="assigned_to">Assigned to</th>
                                <th scope="col" data-col="assigned_reason">Assigned reason</th>
                                <th scope="col" data-col="status">Status</th>
                            </tr>
                        </thead>
                        <tbody id="assetsTableBody">
                            <tr id="assetsLoadingRow">
                                <td colspan="19" class="text-center text-muted py-4">Loading assets…</td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="cs-assets-empty-filtered" id="assetsEmptyFiltered" hidden>
                        <div class="cs-empty-state">
                            <div class="cs-empty-icon" aria-hidden="true"><i class="fas fa-filter"></i></div>
                            <div class="cs-empty-text">No assets match your search or filters.</div>
                        </div>
                    </div>
                </div>

                <nav class="cs-assets-pagination" id="assetsPagination" aria-label="Assets pagination"></nav>
            @else
                <div class="cs-empty-state">
                    <div class="cs-empty-icon" aria-hidden="true"><i class="fas fa-box-open"></i></div>
                    <div class="cs-empty-text">No assets found for this case.</div>
                </div>
            @endif
        </div>

        {{-- Comments --}}
        <div class="cs-tab-panel" role="tabpanel" id="panel-comments" aria-labelledby="tab-comments" data-panel="comments" hidden>
            <div class="cs-section-header">
                <h2 class="cs-section-title">Case comments</h2>
                <p class="cs-section-subtitle">Matter-level discussion only (not linked to an asset)</p>
            </div>
            <div class="cs-comments-board" id="caseCommentsBoard" data-comments-scope="case">
                <div class="cs-comments-sticky">
                    <div class="cs-comments-composer-row" data-comments-composer-collapsed>
                        <div class="cs-comments-avatar is-you" aria-hidden="true">{{ strtoupper(mb_substr(Auth::user()->name ?? 'Y', 0, 1)) }}</div>
                        <button type="button" class="cs-comments-pill" data-comments-open>Add a comment…</button>
                        <button type="button" class="cs-comments-send" data-comments-open aria-label="Add comment"><i class="fas fa-paper-plane" aria-hidden="true"></i></button>
                    </div>
                    <div class="cs-comments-composer-expanded" data-comments-composer-expanded hidden>
                        <textarea data-comments-input rows="4" maxlength="5000" placeholder="Add a comment…" aria-label="Add a comment"></textarea>
                        <div class="cs-comments-composer-actions">
                            <p class="cs-case-comment-status" data-comments-status hidden></p>
                            <button type="button" class="cs-btn-secondary" data-comments-cancel>Cancel</button>
                            <button type="button" class="cs-btn-primary" data-comments-submit><i class="fas fa-paper-plane" aria-hidden="true"></i> Comment</button>
                        </div>
                    </div>
                    <div class="cs-comments-toolbar">
                        <div class="cs-comments-search-wrap">
                            <i class="fas fa-search" aria-hidden="true"></i>
                            <input type="search" data-comments-search maxlength="100" placeholder="Search comments…" aria-label="Search comments">
                        </div>
                        <div class="cs-comments-toolbar-meta">
                            <span class="cs-comments-count" data-comments-count id="caseCommentsCountLabel">{{ (int) ($commentCount ?? 0) }} Comment{{ ((int) ($commentCount ?? 0)) === 1 ? '' : 's' }}</span>
                            <select data-comments-sort aria-label="Sort by date">
                                <option value="desc" selected>Newest first</option>
                                <option value="asc">Oldest first</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div data-comments-list id="caseCommentsList">
                    <p class="cs-comments-empty">Open this tab to load comments.</p>
                </div>
            </div>
        </div>

        {{-- Activities --}}
        <div class="cs-tab-panel" role="tabpanel" id="panel-activities" aria-labelledby="tab-activities" data-panel="activities" hidden>
            <div class="cs-section-header">
                <h2 class="cs-section-title">Activity log</h2>
                <p class="cs-section-subtitle">Case activities and follow-ups</p>
            </div>
            <div id="activityList">
                <div class="cs-empty-state">
                    <div class="cs-empty-icon" aria-hidden="true"><i class="fas fa-stream"></i></div>
                    <div class="cs-empty-text">Loading activities…</div>
                </div>
            </div>
        </div>
    </div>

    <div class="cs-asset-detail-modal" id="assetDetailModal" hidden>
        <div class="cs-asset-detail-backdrop" data-asset-detail-close></div>
        <div class="cs-asset-detail-dialog" role="dialog" aria-modal="true" aria-labelledby="assetDetailTitle">
            <div class="cs-asset-detail-header">
                <div>
                    <h2 id="assetDetailTitle">Asset details</h2>
                    <p class="cs-asset-detail-subtitle" id="assetDetailSubtitle"></p>
                </div>
                <button type="button" class="cs-asset-detail-close" data-asset-detail-close aria-label="Close asset details">
                    <i class="fas fa-times" aria-hidden="true"></i>
                </button>
            </div>
            <div class="cs-asset-detail-tabs" role="tablist" aria-label="Asset sections">
                <button type="button" class="cs-asset-detail-tab is-active" role="tab" data-asset-tab="details" aria-selected="true">Details</button>
                <button type="button" class="cs-asset-detail-tab" role="tab" data-asset-tab="comments" aria-selected="false">Comments</button>
                <button type="button" class="cs-asset-detail-tab" role="tab" data-asset-tab="timeline" aria-selected="false">Timeline</button>
            </div>
            <div class="cs-asset-detail-body">
                <div class="cs-asset-detail-panel is-active" data-asset-panel="details" id="assetDetailPanel">
                    <p class="cs-asset-detail-loading">Loading asset details…</p>
                </div>
                <div class="cs-asset-detail-panel" data-asset-panel="comments" id="assetDetailComments" hidden>
                    <div class="cs-comments-board" id="assetCommentsBoard" data-comments-scope="asset">
                        <div class="cs-comments-sticky">
                            <div class="cs-comments-composer-row" data-comments-composer-collapsed>
                                <div class="cs-comments-avatar is-you" aria-hidden="true">{{ strtoupper(mb_substr(Auth::user()->name ?? 'Y', 0, 1)) }}</div>
                                <button type="button" class="cs-comments-pill" data-comments-open>Add a comment…</button>
                                <button type="button" class="cs-comments-send" data-comments-open aria-label="Add comment"><i class="fas fa-paper-plane" aria-hidden="true"></i></button>
                            </div>
                            <div class="cs-comments-composer-expanded" data-comments-composer-expanded hidden>
                                <textarea data-comments-input rows="3" maxlength="5000" placeholder="Add a comment…" aria-label="Add a comment"></textarea>
                                <div class="cs-comments-composer-actions">
                                    <p class="cs-case-comment-status" data-comments-status hidden></p>
                                    <button type="button" class="cs-btn-secondary" data-comments-cancel>Cancel</button>
                                    <button type="button" class="cs-btn-primary" data-comments-submit><i class="fas fa-paper-plane" aria-hidden="true"></i> Comment</button>
                                </div>
                            </div>
                            <div class="cs-comments-toolbar">
                                <div class="cs-comments-search-wrap">
                                    <i class="fas fa-search" aria-hidden="true"></i>
                                    <input type="search" data-comments-search maxlength="100" placeholder="Search comments…" aria-label="Search comments">
                                </div>
                                <div class="cs-comments-toolbar-meta">
                                    <span class="cs-comments-count" data-comments-count>0 Comments</span>
                                    <select data-comments-sort aria-label="Sort by date">
                                        <option value="desc" selected>Newest first</option>
                                        <option value="asc">Oldest first</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div data-comments-list>
                            <p class="cs-comments-empty">Open this tab to load comments.</p>
                        </div>
                    </div>
                </div>
                <div class="cs-asset-detail-panel" data-asset-panel="timeline" id="assetDetailTimeline" hidden>
                    <p class="cs-asset-detail-loading">Loading timeline…</p>
                </div>
            </div>
        </div>
    </div>
</div>

@include('backend.cases.partials.close-case-modal')

@php
    $assetDetailUrlTemplate = route('admin.cases.assets.show', ['id' => $case->id, 'itemId' => 0]);
    $assetCommentsUrlTemplate = route('admin.cases.assets.comments', ['id' => $case->id, 'itemId' => 0]);
    $assetCommentsStoreUrlTemplate = route('admin.cases.assets.comments.store', ['id' => $case->id, 'itemId' => 0]);
    $assetTimelineUrlTemplate = route('admin.cases.assets.timeline', ['id' => $case->id, 'itemId' => 0]);
    $assetCommentResponsesUrlTemplate = route('admin.cases.assets.commentResponses', [
        'id' => $case->id,
        'itemId' => 0,
        'commentId' => 0,
    ]);
    $assetCommentResponseStoreUrlTemplate = route('admin.cases.assets.commentResponses.store', [
        'id' => $case->id,
        'itemId' => 0,
        'commentId' => 0,
    ]);
    $caseCommentResponsesUrlTemplate = route('admin.cases.commentResponses', [
        'id' => $case->id,
        'commentId' => 0,
    ]);
    $caseCommentResponseStoreUrlTemplate = route('admin.cases.commentResponses.store', [
        'id' => $case->id,
        'commentId' => 0,
    ]);
    $caseCommentLikeUrlTemplate = route('admin.cases.comments.like', [
        'id' => $case->id,
        'commentId' => 0,
    ]);
    $caseCommentUnlikeUrlTemplate = route('admin.cases.comments.unlike', [
        'id' => $case->id,
        'commentId' => 0,
    ]);
@endphp
@push('scripts')
<script src="{{ asset('backend-assets/js/case-comments-board.js') }}"></script>
<script src="{{ asset('backend-assets/js/case-close-modal.js') }}"></script>
<script src="{{ asset('backend-assets/js/case-show-tabs.js') }}"></script>
<script src="{{ asset('backend-assets/js/case-show-page.js') }}"></script>
<script>
if (typeof initCaseCloseModal === 'function') initCaseCloseModal();
if (typeof initCaseShowPage === 'function') {
    initCaseShowPage({
        caseId: {{ $case->id }},
        csrfToken: @json(csrf_token()),
        activityListUrl: @json(route('admin.case.activity.list', $case->id)),
        assetCount: {{ $assetCount }},
        caseCommentsUrl: @json(route('admin.cases.comments', $case->id)),
        caseCommentStoreUrl: @json(route('admin.cases.comments.store', $case->id)),
        caseCommentResponsesUrlTemplate: @json($caseCommentResponsesUrlTemplate),
        caseCommentResponseStoreUrlTemplate: @json($caseCommentResponseStoreUrlTemplate),
        caseCommentLikeUrlTemplate: @json($caseCommentLikeUrlTemplate),
        caseCommentUnlikeUrlTemplate: @json($caseCommentUnlikeUrlTemplate),
        itemDataElementLabels: @json($itemDataElementLabels),
        assetsListUrl: @json(route('admin.cases.assets.list', $case->id)),
        assetDetailUrlTemplate: @json($assetDetailUrlTemplate),
        assetCommentsUrlTemplate: @json($assetCommentsUrlTemplate),
        assetCommentsStoreUrlTemplate: @json($assetCommentsStoreUrlTemplate),
        assetTimelineUrlTemplate: @json($assetTimelineUrlTemplate),
        assetCommentResponsesUrlTemplate: @json($assetCommentResponsesUrlTemplate),
        assetCommentResponseStoreUrlTemplate: @json($assetCommentResponseStoreUrlTemplate),
    });
}
</script>
@endpush
@endsection
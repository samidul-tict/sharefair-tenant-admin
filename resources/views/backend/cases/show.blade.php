@extends('backend.layout.inner-app')
@section('title', 'Case Details | Share Fair')
@section('proxima')

@php
    $caseTypeName = $case->caseType?->name ?? $case->case_type_value;
    $caseStatusName = $case->caseStatus?->name ?? ($case->case_status_value ?? 'N/A');
    $userCount = $case->caseUsers->count();
    $assetCount = (int) ($assetCount ?? 0);
    $locationCount = $locations->count();
    $activityCount = $activityCount ?? 0;

    $roleBadgeClass = function ($row) {
        $rn = strtolower($row->role_name ?? '');
        if ($row->role_value === 'LEGAL_RE' || str_contains($rn, 'employee')) {
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
@endphp

<div class="case-show-modern">
    <div class="cs-container">
        @if (session('success'))
            <div class="alert alert-success cs-flash-alert" role="alert">{{ session('success') }}</div>
        @endif
        @if (request('distributed'))
            <div class="alert alert-success cs-flash-alert" role="alert">Assets distributed successfully.</div>
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
                <div class="cs-case-number">{{ $case->case_number }}</div>
                <div class="cs-case-type-badge">{{ $caseTypeName }}</div>
                <div class="cs-status-badge {{ $case->is_active ? 'cs-status-active' : 'cs-status-inactive' }}">
                    <span class="cs-status-dot" aria-hidden="true"></span>
                    {{ $case->is_active ? 'Active' : 'Inactive' }}
                </div>
                <div class="cs-case-status-badge">
                    <span class="cs-status-dot" aria-hidden="true"></span>
                    {{ $caseStatusName }}
                </div>
            </div>
            <div class="cs-header-actions">
                @if(($showDistributionSummary ?? false) && $canEditCase)
                <a href="{{ route('admin.cases.distribute.review', $case->id) }}" class="cs-btn-primary cs-btn-distribute">
                    <i class="fas fa-balance-scale" aria-hidden="true"></i>
                    {{ ($canDistribute ?? false) ? 'Distribute assets' : 'Distribution summary' }}
                </a>
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
                <i class="fas fa-users" aria-hidden="true"></i> Users
                <span class="cs-tab-count">{{ $userCount }}</span>
            </button>
            <button type="button" class="cs-tab" role="tab" id="tab-assets" aria-selected="false" aria-controls="panel-assets" data-tab="assets">
                <i class="fas fa-box" aria-hidden="true"></i> Assets
                <span class="cs-tab-count">{{ $assetCount }}</span>
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
                        <div class="cs-kv-row"><dt>Court</dt><dd>{{ $case->court_name ?: '—' }}</dd></div>
                        <div class="cs-kv-row"><dt>Matter type</dt><dd>{{ $caseTypeName }}</dd></div>
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
                            <article class="cs-user-card-v2" data-mapping-id="{{ $row->id }}">
                                <div class="cs-user-avatar" aria-hidden="true">{{ $userInitials($row->user_name) }}</div>
                                <div class="cs-user-body">
                                    <div class="cs-user-name">{{ $row->user_name ?? 'N/A' }}</div>
                                    <div class="cs-user-meta">
                                        <span><i class="fas fa-envelope" aria-hidden="true"></i> {{ $row->user_email ?? 'N/A' }}</span>
                                        <span><i class="fas fa-phone" aria-hidden="true"></i> {{ $row->user_phone ?? 'N/A' }}</span>
                                        @if($showDistributionCaps && in_array($row->role_value, ['PL', 'DEF'], true))
                                        <span><i class="fas fa-hand-holding-usd" aria-hidden="true"></i> Value cap: {{ $itemMoney($row->distribution_value_cap ?? null) }}</span>
                                        @endif
                                    </div>
                                    <span class="cs-role-badge {{ $roleBadgeClass($row) }}">{{ $row->role_name ?? 'Not Assigned' }}</span>
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
                            <input type="search" id="assetsSearch" class="cs-assets-search" placeholder="Search by name, brand, assignee…" autocomplete="off">
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
                        <p class="cs-assets-columns-title">Show columns</p>
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
</div>

@push('scripts')
<script>
var caseId = {{ $case->id }};
var itemDataElementLabels = @json($itemDataElementLabels);
var assetsListUrl = @json(route('admin.cases.assets.list', $case->id));

function activateTab(tabId) {
    document.querySelectorAll('.cs-tab').forEach(function(tab) {
        var active = tab.getAttribute('data-tab') === tabId;
        tab.classList.toggle('is-active', active);
        tab.setAttribute('aria-selected', active ? 'true' : 'false');
    });
    document.querySelectorAll('.cs-tab-panel').forEach(function(panel) {
        var active = panel.getAttribute('data-panel') === tabId;
        panel.classList.toggle('is-active', active);
        panel.hidden = !active;
    });
    if (tabId === 'assets' && typeof scheduleCaseAssetsLoad === 'function') {
        scheduleCaseAssetsLoad();
    }
}

document.querySelectorAll('.cs-tab').forEach(function(tab) {
    tab.addEventListener('click', function() {
        activateTab(tab.getAttribute('data-tab'));
    });
});

document.querySelectorAll('[data-jump-tab]').forEach(function(el) {
    el.addEventListener('click', function() {
        activateTab(el.getAttribute('data-jump-tab'));
    });
});

$(document).ready(function() {
    var notesToggle = document.getElementById('matterNotesToggle');
    if (notesToggle) {
        notesToggle.addEventListener('click', function() {
            var block = document.getElementById('matterNotesBlock');
            var preview = document.getElementById('matterNotesText');
            var full = document.getElementById('matterNotesFull');
            if (!block || !preview || !full) return;
            var expanded = block.getAttribute('data-expanded') === 'true';
            expanded = !expanded;
            block.setAttribute('data-expanded', expanded ? 'true' : 'false');
            preview.hidden = expanded;
            full.hidden = !expanded;
            notesToggle.textContent = expanded ? 'Show less' : 'Read more';
            notesToggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        });
    }

    loadActivities();

    function loadActivities(page) {
        page = page || 1;
        $.ajax({
            url: "{{ route('admin.case.activity.list', $case->id) }}",
            type: 'GET',
            data: { page: page },
            success: function(res) {
                function escapeHtml(text) {
                    if (text === null || text === undefined) return '';
                    return String(text)
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#039;');
                }
                var html = '';
                if (res.activities && res.activities.length) {
                    html += '<div class="cs-timeline">';
                    res.activities.forEach(function(row, idx) {
                        var userName = row.user_name || (row.user && row.user.name) || row.activity_by || 'N/A';
                        var subject = row.subject || 'Activity';
                        var notes = row.notes || '';
                        var nextDate = row.next_follow_up_date || '';
                        var created = row.created_date || '';
                        html += '<article class="cs-timeline-item">' +
                            '<div class="cs-timeline-rail" aria-hidden="true"><span class="cs-timeline-dot"></span>' +
                            (idx < res.activities.length - 1 ? '<span class="cs-timeline-line"></span>' : '') +
                            '</div>' +
                            '<div class="cs-timeline-card">' +
                            '<h3 class="cs-timeline-subject">' + escapeHtml(subject) + '</h3>' +
                            '<div class="cs-timeline-meta">' + escapeHtml(created) + ' · ' + escapeHtml(userName) + '</div>' +
                            (notes ? '<p class="cs-timeline-notes">' + escapeHtml(notes) + '</p>' : '') +
                            (nextDate ? '<div class="cs-timeline-followup"><i class="fas fa-calendar-check" aria-hidden="true"></i> Next follow-up: ' + escapeHtml(nextDate) + '</div>' : '') +
                            '</div></article>';
                    });
                    html += '</div>';
                    if (res.pagination && res.pagination.last_page > 1) {
                        var p = res.pagination;
                        html += '<nav class="cs-activity-pagination" aria-label="Case activities pagination"><ul class="pagination mb-0 mt-3 flex-wrap justify-content-end">';
                        if (p.prev_page_url) {
                            html += '<li class="page-item"><a class="page-link cs-activity-page-link" href="#" data-page="' + (p.current_page - 1) + '" aria-label="Previous">Previous</a></li>';
                        }
                        for (var i = 1; i <= p.last_page; i++) {
                            var active = i === p.current_page ? ' active' : '';
                            html += '<li class="page-item' + active + '"><a class="page-link cs-activity-page-link" href="#" data-page="' + i + '">' + i + '</a></li>';
                        }
                        if (p.next_page_url) {
                            html += '<li class="page-item"><a class="page-link cs-activity-page-link" href="#" data-page="' + (p.current_page + 1) + '" aria-label="Next">Next</a></li>';
                        }
                        html += '</ul><p class="cs-activity-pagination-info text-muted small mb-0 mt-1">Showing ' + (p.per_page * (p.current_page - 1) + 1) + '–' + Math.min(p.current_page * p.per_page, p.total) + ' of ' + p.total + '</p></nav>';
                    }
                } else {
                    html = '<div class="cs-empty-state"><div class="cs-empty-icon" aria-hidden="true"><i class="fas fa-stream"></i></div><div class="cs-empty-text">No activities found.</div></div>';
                }
                $('#activityList').html(html);
            }
        });
    }

    $(document).on('click', '#activityList .cs-activity-page-link', function(e) {
        e.preventDefault();
        var page = $(this).data('page');
        if (page) loadActivities(page);
    });

    (function initAssetsTable() {
        var table = document.getElementById('assetsTable');
        if (!table) return;

        var tbody = document.getElementById('assetsTableBody');
        var searchInput = document.getElementById('assetsSearch');
        var filterStatus = document.getElementById('assetsFilterStatus');
        var filterCategory = document.getElementById('assetsFilterCategory');
        var filterLocation = document.getElementById('assetsFilterLocation');
        var perPageSelect = document.getElementById('assetsPerPage');
        var paginationEl = document.getElementById('assetsPagination');
        var emptyFiltered = document.getElementById('assetsEmptyFiltered');
        var countSubtitle = document.getElementById('assetsCountSubtitle');
        var columnsBtn = document.getElementById('assetsColumnsBtn');
        var columnsPanel = document.getElementById('assetsColumnsPanel');
        var columnsReset = document.getElementById('assetsColumnsReset');
        var columnCheckboxes = columnsPanel
            ? Array.prototype.slice.call(columnsPanel.querySelectorAll('input[data-col]'))
            : [];

        var ASSET_COLS = [
            'index', 'name', 'location', 'category', 'other_category', 'condition', 'brand',
            'other_brand', 'purchase_year', 'purchase_price', 'estimated_value', 'concluded_price',
            'accessories_status', 'original_packaging', 'valid_warranty', 'marital_asset',
            'assigned_to', 'assigned_reason', 'status'
        ];

        var STORAGE_KEY = 'case-' + caseId + '-assets-columns';
        var DEFAULT_COLS = {
            index: true,
            name: true,
            location: true,
            category: true,
            other_category: false,
            condition: true,
            brand: true,
            other_brand: false,
            purchase_year: false,
            purchase_price: true,
            estimated_value: true,
            concluded_price: true,
            accessories_status: false,
            original_packaging: false,
            valid_warranty: false,
            marital_asset: true,
            assigned_to: true,
            assigned_reason: false,
            status: true
        };

        var currentPage = 1;
        var searchTimer = null;
        var assetsLoading = false;
        var assetsLoadedOnce = false;
        var filtersPopulated = false;
        var totalInCase = {{ $assetCount }};

        function escapeHtml(text) {
            if (text === null || text === undefined) return '';
            return String(text)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function itemLabel(value) {
            if (value === null || value === '') return value;
            return itemDataElementLabels[value] || value;
        }

        function loadColumnPrefs() {
            try {
                var stored = localStorage.getItem(STORAGE_KEY);
                if (stored) return Object.assign({}, DEFAULT_COLS, JSON.parse(stored));
            } catch (e) { /* ignore */ }
            return Object.assign({}, DEFAULT_COLS);
        }

        function saveColumnPrefs(prefs) {
            try {
                localStorage.setItem(STORAGE_KEY, JSON.stringify(prefs));
            } catch (e) { /* ignore */ }
        }

        function applyColumns(prefs) {
            table.querySelectorAll('[data-col]').forEach(function(cell) {
                var col = cell.getAttribute('data-col');
                var visible = prefs[col] !== false;
                cell.classList.toggle('cs-assets-col-hidden', !visible);
            });
            columnCheckboxes.forEach(function(cb) {
                var col = cb.getAttribute('data-col');
                if (cb.disabled) return;
                cb.checked = prefs[col] !== false;
            });
        }

        function populateFilterSelect(selectEl, values, allLabel) {
            if (!selectEl) return;
            var current = selectEl.value;
            selectEl.innerHTML = '<option value="">' + allLabel + '</option>';
            (values || []).forEach(function(value) {
                var option = document.createElement('option');
                option.value = value;
                option.textContent = itemLabel(value) || value;
                selectEl.appendChild(option);
            });
            if (current) selectEl.value = current;
        }

        function populateLocationSelect(locations) {
            if (!filterLocation) return;
            var current = filterLocation.value;
            filterLocation.innerHTML = '<option value="">All locations</option>';
            (locations || []).forEach(function(loc) {
                var option = document.createElement('option');
                option.value = String(loc.id);
                option.textContent = loc.name || ('#' + loc.id);
                filterLocation.appendChild(option);
            });
            if (current) filterLocation.value = current;
        }

        function populateFilters(filters) {
            if (filtersPopulated || !filters) return;
            filtersPopulated = true;
            populateFilterSelect(filterStatus, filters.statuses, 'All statuses');
            populateFilterSelect(filterCategory, filters.categories, 'All categories');
            populateLocationSelect(filters.locations);
        }

        function renderAssetRow(row) {
            var html = '<tr>';
            ASSET_COLS.forEach(function(col) {
                var cls = col === 'name' ? ' class="cs-table-name"' : '';
                html += '<td data-col="' + col + '"' + cls + '>' + escapeHtml(row[col]) + '</td>';
            });
            html += '</tr>';
            return html;
        }

        function renderPagination(pagination) {
            if (!paginationEl || !pagination) {
                if (paginationEl) paginationEl.innerHTML = '';
                return;
            }

            var total = pagination.total;
            var page = pagination.current_page;
            var perPage = pagination.per_page;
            var lastPage = pagination.last_page;

            if (total <= perPage) {
                paginationEl.innerHTML = total
                    ? '<p class="cs-assets-pagination-info">Showing all ' + total + ' matching asset' + (total === 1 ? '' : 's') + '</p>'
                    : '';
                return;
            }

            var start = (page - 1) * perPage + 1;
            var end = Math.min(page * perPage, total);
            var html = '<ul class="cs-assets-page-list">';
            if (page > 1) {
                html += '<li><button type="button" class="cs-assets-page-btn" data-page="' + (page - 1) + '" aria-label="Previous page">Previous</button></li>';
            }
            for (var i = 1; i <= lastPage; i++) {
                if (lastPage > 7 && i > 2 && i < lastPage - 1 && Math.abs(i - page) > 1) {
                    if (i === 3 || i === lastPage - 2) {
                        html += '<li><span class="cs-assets-page-ellipsis" aria-hidden="true">…</span></li>';
                    }
                    continue;
                }
                var active = i === page ? ' is-active' : '';
                html += '<li><button type="button" class="cs-assets-page-btn' + active + '" data-page="' + i + '"' + (i === page ? ' aria-current="page"' : '') + '>' + i + '</button></li>';
            }
            if (page < lastPage) {
                html += '<li><button type="button" class="cs-assets-page-btn" data-page="' + (page + 1) + '" aria-label="Next page">Next</button></li>';
            }
            html += '</ul>';
            html += '<p class="cs-assets-pagination-info">Showing ' + start + '–' + end + ' of ' + total + ' matching assets</p>';
            paginationEl.innerHTML = html;
        }

        function updateCountSubtitle(pagination, filteredTotal) {
            if (!countSubtitle) return;
            var total = filteredTotal != null ? filteredTotal : (pagination ? pagination.total : 0);
            if (typeof pagination !== 'undefined' && pagination && totalInCase && total < totalInCase) {
                countSubtitle.textContent = total + ' of ' + totalInCase + ' assets shown';
            } else {
                countSubtitle.textContent = totalInCase + ' asset' + (totalInCase === 1 ? '' : 's') + ' in this case';
            }
        }

        function loadCaseAssets(page) {
            page = page || 1;
            currentPage = page;
            if (assetsLoading) return;
            assetsLoading = true;

            if (tbody) {
                tbody.innerHTML = '<tr><td colspan="19" class="text-center text-muted py-4">Loading assets…</td></tr>';
            }
            if (emptyFiltered) emptyFiltered.hidden = true;
            table.hidden = false;

            $.ajax({
                url: assetsListUrl,
                type: 'GET',
                data: {
                    page: page,
                    per_page: perPageSelect ? perPageSelect.value : 25,
                    search: searchInput ? searchInput.value : '',
                    status: filterStatus ? filterStatus.value : '',
                    category: filterCategory ? filterCategory.value : '',
                    location_id: filterLocation ? filterLocation.value : ''
                },
                success: function(res) {
                    assetsLoadedOnce = true;
                    populateFilters(res.filters);
                    if (res.total_in_case != null) totalInCase = res.total_in_case;

                    var items = res.items || [];
                    if (!items.length) {
                        tbody.innerHTML = '';
                        table.hidden = true;
                        if (emptyFiltered) emptyFiltered.hidden = false;
                        if (countSubtitle) {
                            countSubtitle.textContent = 'No assets match your filters (' + totalInCase + ' total in this case)';
                        }
                        renderPagination(res.pagination);
                        return;
                    }

                    tbody.innerHTML = items.map(renderAssetRow).join('');
                    table.hidden = false;
                    if (emptyFiltered) emptyFiltered.hidden = true;
                    updateCountSubtitle(res.pagination);
                    renderPagination(res.pagination);
                },
                error: function() {
                    tbody.innerHTML = '<tr><td colspan="19" class="text-center text-danger py-4">Unable to load assets. Please try again.</td></tr>';
                },
                complete: function() {
                    assetsLoading = false;
                }
            });
        }

        window.scheduleCaseAssetsLoad = function() {
            if (!assetsLoadedOnce && !assetsLoading) {
                loadCaseAssets(1);
            }
        };
        window.loadCaseAssets = loadCaseAssets;

        var columnPrefs = loadColumnPrefs();
        applyColumns(columnPrefs);

        function onFilterChange() {
            currentPage = 1;
            loadCaseAssets(1);
        }

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(onFilterChange, 300);
            });
        }
        [filterStatus, filterCategory, filterLocation, perPageSelect].forEach(function(el) {
            if (el) el.addEventListener('change', onFilterChange);
        });

        if (paginationEl) {
            paginationEl.addEventListener('click', function(e) {
                var btn = e.target.closest('[data-page]');
                if (!btn) return;
                e.preventDefault();
                var page = parseInt(btn.getAttribute('data-page'), 10) || 1;
                loadCaseAssets(page);
                table.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            });
        }

        columnCheckboxes.forEach(function(cb) {
            cb.addEventListener('change', function() {
                var col = cb.getAttribute('data-col');
                columnPrefs[col] = cb.checked;
                saveColumnPrefs(columnPrefs);
                applyColumns(columnPrefs);
            });
        });

        if (columnsReset) {
            columnsReset.addEventListener('click', function() {
                columnPrefs = Object.assign({}, DEFAULT_COLS);
                saveColumnPrefs(columnPrefs);
                applyColumns(columnPrefs);
            });
        }

        if (columnsBtn && columnsPanel) {
            columnsBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                var open = columnsPanel.hidden;
                columnsPanel.hidden = !open;
                columnsBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
            document.addEventListener('click', function(e) {
                if (columnsPanel.hidden) return;
                if (columnsPanel.contains(e.target) || columnsBtn.contains(e.target)) return;
                columnsPanel.hidden = true;
                columnsBtn.setAttribute('aria-expanded', 'false');
            });
        }
    })();

});
</script>
@endpush
@endsection

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
        @if (session('error'))
            <div class="alert alert-danger cs-flash-alert" role="alert">{{ session('error') }}</div>
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
                <i class="fas fa-users" aria-hidden="true"></i> Users
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
<script>
var caseId = {{ $case->id }};
var csrfToken = @json(csrf_token());
var caseCommentsUrl = @json(route('admin.cases.comments', $case->id));
var caseCommentStoreUrl = @json(route('admin.cases.comments.store', $case->id));
var caseCommentResponsesUrlTemplate = @json($caseCommentResponsesUrlTemplate);
var caseCommentResponseStoreUrlTemplate = @json($caseCommentResponseStoreUrlTemplate);
var caseCommentLikeUrlTemplate = @json($caseCommentLikeUrlTemplate);
var caseCommentUnlikeUrlTemplate = @json($caseCommentUnlikeUrlTemplate);
var itemDataElementLabels = @json($itemDataElementLabels);
var assetsListUrl = @json(route('admin.cases.assets.list', $case->id));
var assetDetailUrlTemplate = @json($assetDetailUrlTemplate);
var assetCommentsUrlTemplate = @json($assetCommentsUrlTemplate);
var assetCommentsStoreUrlTemplate = @json($assetCommentsStoreUrlTemplate);
var assetTimelineUrlTemplate = @json($assetTimelineUrlTemplate);
var assetCommentResponsesUrlTemplate = @json($assetCommentResponsesUrlTemplate);
var assetCommentResponseStoreUrlTemplate = @json($assetCommentResponseStoreUrlTemplate);

if (typeof initCaseCloseModal === 'function') initCaseCloseModal();

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
    if (tabId === 'comments' && typeof window.loadCaseComments === 'function') {
        window.loadCaseComments();
    }
    if (tabId === 'activities' && typeof window.loadMoreCaseActivitiesIfNeeded === 'function') {
        window.loadMoreCaseActivitiesIfNeeded();
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

    var caseActivityState = {
        page: 0,
        lastPage: 1,
        loading: false,
        hasMore: true,
        observer: null
    };

    function escapeTimelineHtml(text) {
        if (text === null || text === undefined) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatTimelineDate(value) {
        if (!value) return '';
        var d = new Date(value);
        if (isNaN(d.getTime())) return String(value);
        return d.toLocaleString(undefined, {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: 'numeric',
            minute: '2-digit'
        });
    }

    function buildTimelineItemHtml(row) {
        var userName = row.created_by_name || row.user_name || (row.user && row.user.name) || row.activity_by || 'N/A';
        var subject = row.subject || 'Activity';
        var notes = row.notes || '';
        var nextDate = row.next_follow_up_date || '';
        var created = formatTimelineDate(row.created_date) || (row.created_date || '');
        return (
            '<article class="cs-timeline-item">' +
            '<div class="cs-timeline-rail" aria-hidden="true"><span class="cs-timeline-dot"></span><span class="cs-timeline-line"></span></div>' +
            '<div class="cs-timeline-card">' +
            '<h3 class="cs-timeline-subject">' + escapeTimelineHtml(subject) + '</h3>' +
            '<div class="cs-timeline-meta">' + escapeTimelineHtml(created) + ' · ' + escapeTimelineHtml(userName) + '</div>' +
            (notes ? '<p class="cs-timeline-notes">' + escapeTimelineHtml(notes) + '</p>' : '') +
            (nextDate ? '<div class="cs-timeline-followup"><i class="fas fa-calendar-check" aria-hidden="true"></i> Next follow-up: ' + escapeTimelineHtml(nextDate) + '</div>' : '') +
            '</div></article>'
        );
    }

    function syncTimelineRails(timelineEl) {
        if (!timelineEl) return;
        var items = timelineEl.querySelectorAll('.cs-timeline-item');
        items.forEach(function(item, idx) {
            var rail = item.querySelector('.cs-timeline-rail');
            if (!rail) return;
            var line = rail.querySelector('.cs-timeline-line');
            var needsLine = idx < items.length - 1;
            if (needsLine && !line) {
                rail.insertAdjacentHTML('beforeend', '<span class="cs-timeline-line"></span>');
            } else if (!needsLine && line) {
                line.remove();
            }
        });
    }

    function ensureTimelineShell(container, loadingText) {
        var timeline = container.querySelector('.cs-timeline');
        var sentinel = container.querySelector('.cs-timeline-sentinel');
        var status = container.querySelector('.cs-timeline-status');
        if (!timeline) {
            container.innerHTML =
                '<div class="cs-timeline"></div>' +
                '<div class="cs-timeline-sentinel" aria-hidden="true"></div>' +
                '<div class="cs-timeline-status" role="status">' + (loadingText || 'Loading activities…') + '</div>';
            timeline = container.querySelector('.cs-timeline');
            sentinel = container.querySelector('.cs-timeline-sentinel');
            status = container.querySelector('.cs-timeline-status');
        }
        return { timeline: timeline, sentinel: sentinel, status: status };
    }

    function setTimelineStatus(statusEl, text) {
        if (!statusEl) return;
        statusEl.textContent = text || '';
        statusEl.hidden = !text;
    }

    function loadActivities(reset) {
        var list = document.getElementById('activityList');
        if (!list) return;
        if (caseActivityState.loading) return;
        if (!reset && !caseActivityState.hasMore) return;

        if (reset) {
            caseActivityState.page = 0;
            caseActivityState.lastPage = 1;
            caseActivityState.hasMore = true;
            list.innerHTML = '';
            if (caseActivityState.observer) {
                caseActivityState.observer.disconnect();
                caseActivityState.observer = null;
            }
        }

        var nextPage = caseActivityState.page + 1;
        var shell = ensureTimelineShell(list, nextPage === 1 ? 'Loading activities…' : 'Loading more…');
        setTimelineStatus(shell.status, nextPage === 1 ? 'Loading activities…' : 'Loading more…');
        caseActivityState.loading = true;

        $.ajax({
            url: "{{ route('admin.case.activity.list', $case->id) }}",
            type: 'GET',
            data: { page: nextPage },
            success: function(res) {
                var activities = res.activities || [];
                var pagination = res.pagination || {};
                var currentPage = Number(pagination.current_page || nextPage);
                var lastPage = Number(pagination.last_page || currentPage);

                if (currentPage === 1 && !activities.length) {
                    list.innerHTML = '<div class="cs-empty-state"><div class="cs-empty-icon" aria-hidden="true"><i class="fas fa-stream"></i></div><div class="cs-empty-text">No activities found.</div></div>';
                    caseActivityState.hasMore = false;
                    caseActivityState.loading = false;
                    return;
                }

                shell = ensureTimelineShell(list);
                var html = '';
                activities.forEach(function(row) {
                    html += buildTimelineItemHtml(row);
                });
                shell.timeline.insertAdjacentHTML('beforeend', html);
                syncTimelineRails(shell.timeline);

                caseActivityState.page = currentPage;
                caseActivityState.lastPage = lastPage;
                caseActivityState.hasMore = currentPage < lastPage;
                setTimelineStatus(
                    shell.status,
                    caseActivityState.hasMore ? '' : (activities.length || shell.timeline.children.length ? '' : 'No activities found.')
                );

                if (!caseActivityState.observer && shell.sentinel) {
                    caseActivityState.observer = new IntersectionObserver(function(entries) {
                        if (!entries.some(function(entry) { return entry.isIntersecting; })) return;
                        if (caseActivityState.hasMore && !caseActivityState.loading) {
                            loadActivities(false);
                        }
                    }, { root: null, rootMargin: '160px 0px' });
                    caseActivityState.observer.observe(shell.sentinel);
                }
                window.loadMoreCaseActivitiesIfNeeded = function() {
                    var sentinel = document.querySelector('#activityList .cs-timeline-sentinel');
                    if (!sentinel || !caseActivityState.hasMore || caseActivityState.loading) return;
                    var rect = sentinel.getBoundingClientRect();
                    if (rect.top <= (window.innerHeight + 160)) {
                        loadActivities(false);
                    }
                };
            },
            error: function() {
                shell = ensureTimelineShell(list);
                if (!shell.timeline.children.length) {
                    list.innerHTML = '<div class="cs-empty-state"><div class="cs-empty-icon" aria-hidden="true"><i class="fas fa-stream"></i></div><div class="cs-empty-text">Unable to load activities.</div></div>';
                } else {
                    setTimelineStatus(shell.status, 'Unable to load more activities.');
                }
            },
            complete: function() {
                caseActivityState.loading = false;
                if (caseActivityState.hasMore) {
                    var status = document.querySelector('#activityList .cs-timeline-status');
                    if (status && !status.textContent) setTimelineStatus(status, '');
                }
            }
        });
    }

    loadActivities(true);

    function commentUrlReplace(template, commentId) {
        return String(template).replace(/\/comments\/0(\/|$)/, '/comments/' + commentId + '$1');
    }

    function assetCommentPath(template, itemId, commentId) {
        var url = String(template).replace(/\/assets\/0\//, '/assets/' + itemId + '/');
        if (commentId != null) {
            url = url.replace(/\/comments\/0\//, '/comments/' + commentId + '/');
        }
        return url;
    }

    var caseCommentsBoard = window.CaseCommentsBoard && window.CaseCommentsBoard.create({
        root: '#caseCommentsBoard',
        csrfToken: csrfToken,
        listUrl: caseCommentsUrl,
        storeUrl: caseCommentStoreUrl,
        responsesUrl: function(commentId) { return commentUrlReplace(caseCommentResponsesUrlTemplate, commentId); },
        responseStoreUrl: function(commentId) { return commentUrlReplace(caseCommentResponseStoreUrlTemplate, commentId); },
        likeUrl: function(commentId) { return commentUrlReplace(caseCommentLikeUrlTemplate, commentId); },
        unlikeUrl: function(commentId) { return commentUrlReplace(caseCommentUnlikeUrlTemplate, commentId); },
        onCountChange: function(total) {
            var el = document.getElementById('caseCommentsTabCount');
            if (el) el.textContent = String(total);
        }
    });

    window.loadCaseComments = function() {
        if (caseCommentsBoard) caseCommentsBoard.load();
    };

    var assetCommentConfig = {
        root: '#assetCommentsBoard',
        csrfToken: csrfToken,
        itemId: 0,
        listUrl: '',
        storeUrl: '',
        responsesUrl: function(commentId) {
            return assetCommentPath(assetCommentResponsesUrlTemplate, assetCommentConfig.itemId, commentId);
        },
        responseStoreUrl: function(commentId) {
            return assetCommentPath(assetCommentResponseStoreUrlTemplate, assetCommentConfig.itemId, commentId);
        },
        likeUrl: function(commentId) { return commentUrlReplace(caseCommentLikeUrlTemplate, commentId); },
        unlikeUrl: function(commentId) { return commentUrlReplace(caseCommentUnlikeUrlTemplate, commentId); },
        scrollRoot: document.querySelector('#assetDetailModal .cs-asset-detail-body')
    };

    var assetCommentsBoard = window.CaseCommentsBoard && window.CaseCommentsBoard.create(assetCommentConfig);

    window.prepareAssetCommentsBoard = function(itemId) {
        assetCommentConfig.itemId = itemId;
        assetCommentConfig.listUrl = String(assetCommentsUrlTemplate).replace(/\/0(\/comments)/, '/' + itemId + '$1');
        assetCommentConfig.storeUrl = String(assetCommentsStoreUrlTemplate).replace(/\/0(\/comments)/, '/' + itemId + '$1');
        if (assetCommentsBoard) assetCommentsBoard.reset();
    };

    window.loadAssetCommentsBoard = function() {
        if (!assetCommentConfig.itemId || !assetCommentsBoard) return;
        assetCommentsBoard.load();
    };

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
        var ORDER_STORAGE_KEY = STORAGE_KEY + '-order';
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
        var sortBy = 'name';
        var sortOrder = 'asc';
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

        function loadColumnOrder() {
            try {
                var stored = JSON.parse(localStorage.getItem(ORDER_STORAGE_KEY) || '[]');
                if (Array.isArray(stored)) {
                    var valid = stored.filter(function(col, index) {
                        return ASSET_COLS.indexOf(col) !== -1 && stored.indexOf(col) === index;
                    });
                    ASSET_COLS.forEach(function(col) {
                        if (valid.indexOf(col) === -1) valid.push(col);
                    });
                    return valid;
                }
            } catch (e) { /* ignore */ }
            return ASSET_COLS.slice();
        }

        function saveColumnOrder(order) {
            try {
                localStorage.setItem(ORDER_STORAGE_KEY, JSON.stringify(order));
            } catch (e) { /* ignore */ }
        }

        function reorderChildren(container, selector, order) {
            if (!container) return;
            var nodes = {};
            container.querySelectorAll(selector).forEach(function(node) {
                var col = node.getAttribute('data-col');
                if (col) nodes[col] = node;
            });
            order.forEach(function(col) {
                if (nodes[col]) container.appendChild(nodes[col]);
            });
        }

        function applyColumnOrder(order) {
            var headerRow = table.querySelector('thead tr');
            reorderChildren(headerRow, 'th[data-col]', order);

            table.querySelectorAll('tbody tr[data-asset-id]').forEach(function(row) {
                reorderChildren(row, 'td[data-col]', order);
            });

            if (columnsPanel) {
                var grid = columnsPanel.querySelector('.cs-assets-columns-grid');
                var labels = {};
                grid.querySelectorAll('.cs-assets-col-toggle').forEach(function(label) {
                    var input = label.querySelector('input[data-col]');
                    if (input) labels[input.getAttribute('data-col')] = label;
                });
                order.forEach(function(col) {
                    if (labels[col]) grid.appendChild(labels[col]);
                });
            }
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

        function initializeSortableHeaders() {
            table.querySelectorAll('thead th[data-col]').forEach(function(th) {
                var col = th.getAttribute('data-col');
                if (col === 'index' || th.querySelector('[data-asset-sort]')) return;
                var label = th.textContent.trim();
                th.classList.add('cs-assets-sortable');
                th.innerHTML =
                    '<button type="button" class="cs-assets-sort-btn" data-asset-sort="' +
                    col +
                    '" aria-label="Sort by ' +
                    escapeHtml(label) +
                    '">' +
                    '<span>' +
                    escapeHtml(label) +
                    '</span><i class="fas fa-sort cs-assets-sort-icon" aria-hidden="true"></i></button>';
            });
            updateSortHeaders();
        }

        function updateSortHeaders() {
            table.querySelectorAll('thead th[data-col]').forEach(function(th) {
                var col = th.getAttribute('data-col');
                var active = col === sortBy;
                var button = th.querySelector('[data-asset-sort]');
                var icon = button ? button.querySelector('.cs-assets-sort-icon') : null;

                th.removeAttribute('aria-sort');
                if (active) {
                    th.setAttribute('aria-sort', sortOrder === 'asc' ? 'ascending' : 'descending');
                }
                if (button) button.classList.toggle('is-active', active);
                if (icon) {
                    icon.className = 'fas cs-assets-sort-icon ' +
                        (active ? (sortOrder === 'asc' ? 'fa-sort-up' : 'fa-sort-down') : 'fa-sort');
                }
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
            var html = '<tr class="cs-assets-row-clickable" tabindex="0" role="button" data-asset-id="' +
                Number(row.id || 0) +
                '" aria-label="View details for ' +
                escapeHtml(row.name || 'asset') +
                '">';
            columnOrder.forEach(function(col) {
                var cls = col === 'name' ? ' class="cs-table-name"' : '';
                html += '<td data-col="' + col + '"' + cls + '>' + escapeHtml(row[col]) + '</td>';
            });
            html += '</tr>';
            return html;
        }

        function assetDetailUrl(itemId) {
            return String(assetDetailUrlTemplate).replace(/\/0(\?|$)/, '/' + itemId + '$1');
        }

        function assetCommentsUrl(itemId) {
            return String(assetCommentsUrlTemplate).replace(/\/0(\/comments)/, '/' + itemId + '$1');
        }

        function assetTimelineUrl(itemId) {
            return String(assetTimelineUrlTemplate).replace(/\/0(\/timeline)/, '/' + itemId + '$1');
        }

        function assetCommentResponsesUrl(itemId, commentId) {
            return String(assetCommentResponsesUrlTemplate)
                .replace(/\/assets\/0\//, '/assets/' + itemId + '/')
                .replace(/\/comments\/0\//, '/comments/' + commentId + '/');
        }

        var currentAssetId = 0;
        var assetDetailCache = {
            details: null,
            commentsLoadedFor: 0,
            commentsPage: 1,
            timelineLoadedFor: 0,
            timelinePage: 0,
            timelineHasMore: true,
            timelineLoading: false,
            timelineObserver: null
        };

        function setAssetDetailTab(tabId) {
            document.querySelectorAll('[data-asset-tab]').forEach(function(tab) {
                var active = tab.getAttribute('data-asset-tab') === tabId;
                tab.classList.toggle('is-active', active);
                tab.setAttribute('aria-selected', active ? 'true' : 'false');
            });
            document.querySelectorAll('[data-asset-panel]').forEach(function(panel) {
                var active = panel.getAttribute('data-asset-panel') === tabId;
                panel.classList.toggle('is-active', active);
                panel.hidden = !active;
            });
            if (tabId === 'comments' && typeof window.loadAssetCommentsBoard === 'function') {
                window.loadAssetCommentsBoard();
            }
            if (tabId === 'timeline') {
                if (assetDetailCache.timelineLoadedFor === currentAssetId && assetDetailCache.timelinePage > 0) {
                    return;
                }
                loadAssetTimeline(true);
            }
        }

        function disconnectAssetTimelineObserver() {
            if (assetDetailCache.timelineObserver) {
                assetDetailCache.timelineObserver.disconnect();
                assetDetailCache.timelineObserver = null;
            }
        }

        function openAssetDetail(itemId) {
            var modal = document.getElementById('assetDetailModal');
            var detailsEl = document.getElementById('assetDetailPanel');
            var commentsEl = document.getElementById('assetDetailComments');
            var timelineEl = document.getElementById('assetDetailTimeline');
            var title = document.getElementById('assetDetailTitle');
            var subtitle = document.getElementById('assetDetailSubtitle');
            if (!modal || !detailsEl || !itemId) return;

            currentAssetId = itemId;
            disconnectAssetTimelineObserver();
            assetDetailCache = {
                details: null,
                commentsLoadedFor: 0,
                commentsPage: 1,
                timelineLoadedFor: 0,
                timelinePage: 0,
                timelineHasMore: true,
                timelineLoading: false,
                timelineObserver: null
            };

            modal.hidden = false;
            document.body.classList.add('cs-asset-detail-open');
            if (title) title.textContent = 'Asset details';
            if (subtitle) subtitle.textContent = 'Loading…';
            detailsEl.innerHTML = '<p class="cs-asset-detail-loading">Loading asset details…</p>';
            if (typeof window.prepareAssetCommentsBoard === 'function') {
                window.prepareAssetCommentsBoard(itemId);
            }
            if (timelineEl) timelineEl.innerHTML = '<p class="cs-asset-detail-loading">Open this tab to load timeline.</p>';
            setAssetDetailTab('details');

            $.ajax({
                url: assetDetailUrl(itemId),
                type: 'GET',
                success: function(res) {
                    var item = (res && res.item) || {};
                    assetDetailCache.details = item;
                    if (title) title.textContent = item.name || 'Asset details';
                    if (subtitle) subtitle.textContent = item.status ? ('Status: ' + item.status) : '';
                    detailsEl.innerHTML = renderAssetDetail(item);
                },
                error: function() {
                    if (subtitle) subtitle.textContent = '';
                    detailsEl.innerHTML = '<p class="cs-asset-detail-error">Unable to load asset details. Please try again.</p>';
                }
            });
        }

        function closeAssetDetail() {
            var modal = document.getElementById('assetDetailModal');
            if (!modal) return;
            disconnectAssetTimelineObserver();
            modal.hidden = true;
            document.body.classList.remove('cs-asset-detail-open');
            currentAssetId = 0;
        }

        function formatAssetDate(value) {
            if (!value) return '';
            var d = new Date(value);
            if (isNaN(d.getTime())) return String(value);
            return d.toLocaleString(undefined, {
                month: 'short',
                day: 'numeric',
                year: 'numeric',
                hour: 'numeric',
                minute: '2-digit'
            });
        }

        function renderAssetDetail(item) {
            var fields = [
                ['Status', item.status],
                ['Location', item.location],
                ['Category', item.category],
                ['Other category', item.other_category],
                ['Condition', item.condition],
                ['Brand', item.brand],
                ['Other brand', item.other_brand],
                ['Purchase year', item.purchase_year],
                ['Purchase price', item.purchase_price],
                ['Estimated value', item.estimated_value],
                ['Concluded price', item.concluded_price],
                ['Accessories', item.accessories_status],
                ['Original packaging', item.original_packaging],
                ['Valid warranty', item.valid_warranty],
                ['Marital asset', item.marital_asset],
                ['Assigned to', item.assigned_to],
                ['Assigned reason', item.assigned_reason]
            ];

            var media = item.image_url
                ? '<div class="cs-asset-detail-media"><img src="' + escapeHtml(item.image_url) + '" alt="' + escapeHtml(item.name || 'Asset') + '"></div>'
                : '<div class="cs-asset-detail-media is-empty" aria-hidden="true"><i class="fas fa-box-open"></i></div>';

            var grid = fields.map(function(pair) {
                return (
                    '<div class="cs-asset-detail-field">' +
                    '<dt>' + escapeHtml(pair[0]) + '</dt>' +
                    '<dd>' + escapeHtml(pair[1] == null || pair[1] === '' ? '—' : pair[1]) + '</dd>' +
                    '</div>'
                );
            }).join('');

            return (
                '<div class="cs-asset-detail-layout">' +
                media +
                '<div class="cs-asset-detail-main">' +
                '<dl class="cs-asset-detail-grid">' + grid + '</dl>' +
                '<div class="cs-asset-detail-block"><h3>Description</h3><p>' +
                escapeHtml(item.description || '—') +
                '</p></div>' +
                '</div></div>'
            );
        }

        function loadAssetTimeline(reset) {
            var el = document.getElementById('assetDetailTimeline');
            if (!el || !currentAssetId) return;
            if (assetDetailCache.timelineLoading) return;

            if (reset) {
                disconnectAssetTimelineObserver();
                assetDetailCache.timelineLoadedFor = currentAssetId;
                assetDetailCache.timelinePage = 0;
                assetDetailCache.timelineHasMore = true;
                el.innerHTML = '';
            } else if (
                assetDetailCache.timelineLoadedFor !== currentAssetId ||
                !assetDetailCache.timelineHasMore ||
                assetDetailCache.timelinePage < 1
            ) {
                return;
            }

            var nextPage = assetDetailCache.timelinePage + 1;
            var shell = ensureTimelineShell(el, nextPage === 1 ? 'Loading timeline…' : 'Loading more…');
            setTimelineStatus(shell.status, nextPage === 1 ? 'Loading timeline…' : 'Loading more…');
            assetDetailCache.timelineLoading = true;

            $.ajax({
                url: assetTimelineUrl(currentAssetId),
                type: 'GET',
                data: { page: nextPage, limit: 10 },
                success: function(res) {
                    if (currentAssetId !== assetDetailCache.timelineLoadedFor) return;

                    var activities = res.activities || [];
                    var pagination = res.pagination || {};
                    var currentPage = Number(pagination.current_page || nextPage);
                    var lastPage = Number(pagination.last_page || currentPage);

                    if (currentPage === 1 && !activities.length) {
                        el.innerHTML = '<div class="cs-empty-state cs-asset-feed-empty"><div class="cs-empty-icon" aria-hidden="true"><i class="fas fa-stream"></i></div><div class="cs-empty-text">No activities found.</div></div>';
                        assetDetailCache.timelineHasMore = false;
                        assetDetailCache.timelinePage = 1;
                        return;
                    }

                    shell = ensureTimelineShell(el);
                    var html = '';
                    activities.forEach(function(row) {
                        html += buildTimelineItemHtml(row);
                    });
                    shell.timeline.insertAdjacentHTML('beforeend', html);
                    syncTimelineRails(shell.timeline);

                    assetDetailCache.timelinePage = currentPage;
                    assetDetailCache.timelineHasMore = currentPage < lastPage;
                    setTimelineStatus(shell.status, '');

                    var scrollRoot = document.querySelector('#assetDetailModal .cs-asset-detail-body');
                    if (!assetDetailCache.timelineObserver && shell.sentinel) {
                        assetDetailCache.timelineObserver = new IntersectionObserver(function(entries) {
                            if (!entries.some(function(entry) { return entry.isIntersecting; })) return;
                            if (assetDetailCache.timelineHasMore && !assetDetailCache.timelineLoading) {
                                loadAssetTimeline(false);
                            }
                        }, { root: scrollRoot || null, rootMargin: '120px 0px' });
                        assetDetailCache.timelineObserver.observe(shell.sentinel);
                    }
                },
                error: function() {
                    shell = ensureTimelineShell(el);
                    if (!shell.timeline.children.length) {
                        el.innerHTML = '<p class="cs-asset-detail-error">Unable to load timeline.</p>';
                    } else {
                        setTimelineStatus(shell.status, 'Unable to load more activities.');
                    }
                },
                complete: function() {
                    assetDetailCache.timelineLoading = false;
                }
            });
        }
        function renderAssetFeedPagination(pagination, feed) {
            if (!pagination || Number(pagination.last_page || 0) <= 1) return '';
            var page = Number(pagination.current_page || 1);
            var last = Number(pagination.last_page || 1);
            var html = '<nav class="cs-asset-feed-pagination" aria-label="Asset ' + feed + ' pagination"><ul class="cs-assets-page-list">';
            if (page > 1) {
                html += '<li><button type="button" class="cs-assets-page-btn" data-asset-feed-page="' + feed + '" data-page="' + (page - 1) + '">Previous</button></li>';
            }
            for (var i = 1; i <= last; i++) {
                if (last > 7 && i > 2 && i < last - 1 && Math.abs(i - page) > 1) {
                    if (i === 3 || i === last - 2) {
                        html += '<li><span class="cs-assets-page-ellipsis" aria-hidden="true">…</span></li>';
                    }
                    continue;
                }
                html +=
                    '<li><button type="button" class="cs-assets-page-btn' +
                    (i === page ? ' is-active' : '') +
                    '" data-asset-feed-page="' +
                    feed +
                    '" data-page="' +
                    i +
                    '"' +
                    (i === page ? ' aria-current="page"' : '') +
                    '>' +
                    i +
                    '</button></li>';
            }
            if (page < last) {
                html += '<li><button type="button" class="cs-assets-page-btn" data-asset-feed-page="' + feed + '" data-page="' + (page + 1) + '">Next</button></li>';
            }
            html += '</ul></nav>';
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
                    location_id: filterLocation ? filterLocation.value : '',
                    sort_by: sortBy,
                    sort_order: sortOrder
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
                    applyColumnOrder(columnOrder);
                    applyColumns(columnPrefs);
                    updateSortHeaders();
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
        var columnOrder = loadColumnOrder();
        initializeSortableHeaders();
        if (columnsPanel) {
            columnsPanel.querySelectorAll('.cs-assets-col-toggle').forEach(function(label) {
                label.draggable = true;
                label.setAttribute('data-column-item', '');
                label.insertAdjacentHTML(
                    'afterbegin',
                    '<span class="cs-assets-column-drag" aria-hidden="true"><i class="fas fa-grip-vertical"></i></span>'
                );
            });
        }
        applyColumnOrder(columnOrder);
        applyColumns(columnPrefs);

        function onFilterChange() {
            currentPage = 1;
            loadCaseAssets(1);
        }

        if (tbody) {
            tbody.addEventListener('click', function(e) {
                var row = e.target.closest('tr[data-asset-id]');
                if (!row) return;
                var itemId = Number(row.getAttribute('data-asset-id') || 0);
                if (itemId) openAssetDetail(itemId);
            });
            tbody.addEventListener('keydown', function(e) {
                if (e.key !== 'Enter' && e.key !== ' ') return;
                var row = e.target.closest('tr[data-asset-id]');
                if (!row) return;
                e.preventDefault();
                var itemId = Number(row.getAttribute('data-asset-id') || 0);
                if (itemId) openAssetDetail(itemId);
            });
        }

        document.querySelectorAll('[data-asset-tab]').forEach(function(tab) {
            tab.addEventListener('click', function() {
                setAssetDetailTab(tab.getAttribute('data-asset-tab'));
            });
        });

        document.querySelectorAll('[data-asset-detail-close]').forEach(function(el) {
            el.addEventListener('click', closeAssetDetail);
        });
        document.addEventListener('keydown', function(e) {
            if (e.key !== 'Escape') return;
            var modal = document.getElementById('assetDetailModal');
            if (modal && !modal.hidden) closeAssetDetail();
        });

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

        var tableHead = table.querySelector('thead');
        if (tableHead) {
            tableHead.addEventListener('click', function(e) {
                var button = e.target.closest('[data-asset-sort]');
                if (!button || assetsLoading) return;
                var requestedSort = button.getAttribute('data-asset-sort');
                if (requestedSort === sortBy) {
                    sortOrder = sortOrder === 'asc' ? 'desc' : 'asc';
                } else {
                    sortBy = requestedSort;
                    sortOrder = 'asc';
                }
                updateSortHeaders();
                loadCaseAssets(1);
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
                columnOrder = ASSET_COLS.slice();
                saveColumnPrefs(columnPrefs);
                saveColumnOrder(columnOrder);
                applyColumnOrder(columnOrder);
                applyColumns(columnPrefs);
            });
        }

        if (columnsBtn && columnsPanel) {
            var draggedColumnItem = null;
            var columnsGrid = columnsPanel.querySelector('.cs-assets-columns-grid');

            columnsGrid.addEventListener('dragstart', function(e) {
                draggedColumnItem = e.target.closest('[data-column-item]');
                if (!draggedColumnItem) return;
                draggedColumnItem.classList.add('is-dragging');
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData(
                    'text/plain',
                    draggedColumnItem.querySelector('input[data-col]').getAttribute('data-col')
                );
            });

            columnsGrid.addEventListener('dragover', function(e) {
                if (!draggedColumnItem) return;
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                var target = e.target.closest('[data-column-item]');
                if (!target || target === draggedColumnItem) return;
                var rect = target.getBoundingClientRect();
                var insertAfter = e.clientY > rect.top + (rect.height / 2);
                columnsGrid.insertBefore(draggedColumnItem, insertAfter ? target.nextSibling : target);
            });

            columnsGrid.addEventListener('drop', function(e) {
                if (draggedColumnItem) e.preventDefault();
            });

            columnsGrid.addEventListener('dragend', function() {
                if (!draggedColumnItem) return;
                draggedColumnItem.classList.remove('is-dragging');
                draggedColumnItem = null;
                columnOrder = Array.prototype.map.call(
                    columnsGrid.querySelectorAll('input[data-col]'),
                    function(input) { return input.getAttribute('data-col'); }
                );
                saveColumnOrder(columnOrder);
                applyColumnOrder(columnOrder);
            });

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

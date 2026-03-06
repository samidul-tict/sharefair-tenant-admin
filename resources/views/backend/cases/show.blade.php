@extends('backend.layout.inner-app')
@section('title', 'Case Details | Share Fair')
@section('proxima')

<div class="case-show-modern">
    <div class="cs-container">
        <nav class="cs-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route('admin.dashboard') }}">Dashboard</a>
            <span class="cs-breadcrumb-sep" aria-hidden="true">/</span>
            <a href="{{ route('admin.cases.index') }}">Cases</a>
            <span class="cs-breadcrumb-sep" aria-hidden="true">/</span>
            <span class="cs-breadcrumb-current">View</span>
        </nav>
        <!-- Header -->
        <div class="cs-header">
            <div class="cs-case-title">
                <div class="cs-case-number">{{ $case->case_number }}</div>
                <div class="cs-case-type-badge">{{ $case->case_type_value }}</div>
                <div class="cs-status-badge {{ $case->is_active ? 'cs-status-active' : 'cs-status-inactive' }}">
                    <span class="cs-status-dot" aria-hidden="true"></span>
                    {{ $case->is_active ? 'Active' : 'Inactive' }}
                </div>
            </div>
            <a href="{{ route('admin.cases.index') }}" class="cs-btn-secondary">
                <i class="fas fa-arrow-left" aria-hidden="true"></i> Back to cases
            </a>
        </div>

        <!-- Main Grid -->
        <div class="cs-main-grid">
            <!-- Case Information -->
            <div class="cs-card">
                <div class="cs-card-header">
                    <h2 class="cs-card-title">Case Information</h2>
                </div>
                <div class="cs-info-row">
                    <div class="cs-info-label">Case Number</div>
                    <div class="cs-info-value">{{ $case->case_number }}</div>
                </div>
                @if($case->case_description)
                <div class="cs-info-row">
                    <div class="cs-info-label">Description</div>
                    <div class="cs-info-value">{{ $case->case_description }}</div>
                </div>
                @endif
                @if($case->court_name)
                <div class="cs-info-row">
                    <div class="cs-info-label">Court Name</div>
                    <div class="cs-info-value">{{ $case->court_name }}</div>
                </div>
                @endif
                @if($case->sla_deadline)
                <div class="cs-info-row">
                    <div class="cs-info-label">SLA deadline</div>
                    <div class="cs-info-value">{{ $case->sla_deadline->format('Y-m-d') }}</div>
                </div>
                @endif
                @if($case->asset_sla_in_days !== null)
                <div class="cs-info-row">
                    <div class="cs-info-label">Asset SLA (in days)</div>
                    <div class="cs-info-value">{{ $case->asset_sla_in_days }}</div>
                </div>
                @endif
                @if($case->max_number_of_arbitation_per_user !== null)
                <div class="cs-info-row">
                    <div class="cs-info-label">Max number of arbitration allowed per user</div>
                    <div class="cs-info-value">{{ $case->max_number_of_arbitation_per_user }}</div>
                </div>
                @endif
                @if($case->total_items_count !== null)
                <div class="cs-info-row">
                    <div class="cs-info-label">Total items count</div>
                    <div class="cs-info-value">{{ $case->total_items_count }}</div>
                </div>
                @endif
                @if($case->total_items_value !== null)
                <div class="cs-info-row">
                    <div class="cs-info-label">Total items value</div>
                    <div class="cs-info-value">{{ $case->total_items_value }}</div>
                </div>
                @endif
                @if($case->total_marital_assets_count !== null)
                <div class="cs-info-row">
                    <div class="cs-info-label">Total marital assets count</div>
                    <div class="cs-info-value">{{ $case->total_marital_assets_count }}</div>
                </div>
                @endif
                @if($case->total_marital_assets_value !== null)
                <div class="cs-info-row">
                    <div class="cs-info-label">Total marital assets value</div>
                    <div class="cs-info-value">{{ $case->total_marital_assets_value }}</div>
                </div>
                @endif
                @if($case->total_non_marital_assets_count !== null)
                <div class="cs-info-row">
                    <div class="cs-info-label">Total non-marital assets count</div>
                    <div class="cs-info-value">{{ $case->total_non_marital_assets_count }}</div>
                </div>
                @endif
                @if($case->total_dont_want_items_count !== null)
                <div class="cs-info-row">
                    <div class="cs-info-label">Total don't want items count</div>
                    <div class="cs-info-value">{{ $case->total_dont_want_items_count }}</div>
                </div>
                @endif
                @if($case->total_dont_want_items_value !== null)
                <div class="cs-info-row">
                    <div class="cs-info-label">Total don't want items value</div>
                    <div class="cs-info-value">{{ $case->total_dont_want_items_value }}</div>
                </div>
                @endif
                @if($case->total_users !== null)
                <div class="cs-info-row">
                    <div class="cs-info-label">Total users</div>
                    <div class="cs-info-value">{{ $case->total_users }}</div>
                </div>
                @endif
                @if($case->target_value_per_user !== null)
                <div class="cs-info-row">
                    <div class="cs-info-label">Target value per user</div>
                    <div class="cs-info-value">{{ $case->target_value_per_user }}</div>
                </div>
                @endif
                @if($case->distribution_date)
                <div class="cs-info-row">
                    <div class="cs-info-label">Distribution date</div>
                    <div class="cs-info-value">{{ $case->distribution_date->format('Y-m-d') }}</div>
                </div>
                @endif
                @if($case->distributed_by)
                <div class="cs-info-row">
                    <div class="cs-info-label">Distributed by</div>
                    <div class="cs-info-value">{{ $case->distributedBy->name ?? 'N/A' }}</div>
                </div>
                @endif
            </div>

            <!-- Case Users -->
            <div class="cs-card">
                <div class="cs-card-header">
                    <h2 class="cs-card-title">Case Users</h2>
                </div>

                <div id="caseUsersTableWrapper" role="region" aria-label="Case users" aria-live="polite">
                    @if($case->caseUsers->count() > 0)
                        @foreach($case->caseUsers as $row)
                            @php
                                $roleClass = 'cs-role-other';
                                $rn = strtolower($row->role_name ?? '');
                                if ($row->role_value === 'LEGAL_RE' || str_contains($rn, 'employee')) {
                                    $roleClass = 'cs-role-employee';
                                } elseif (str_contains($rn, 'defendant')) {
                                    $roleClass = 'cs-role-defendant';
                                } elseif (str_contains($rn, 'plaintiff')) {
                                    $roleClass = 'cs-role-plaintiff';
                                }
                            @endphp
                            <div class="cs-user-card" data-mapping-id="{{ $row->id }}">
                                <div>
                                    <div class="cs-user-name">{{ $row->user_name ?? 'N/A' }}</div>
                                    <div class="cs-user-email">{{ $row->user_email ?? 'N/A' }}</div>
                                </div>
                                <div class="cs-user-phone">{{ $row->user_phone ?? 'N/A' }}</div>
                                <div>
                                    <span class="cs-role-badge {{ $roleClass }}">{{ $row->role_name ?? 'Not Assigned' }}</span>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <p class="cs-empty-state cs-empty-text">No users assigned to this case.</p>
                    @endif
                </div>

            </div>

            <!-- Case Activities -->
            <div class="cs-card">
                <div class="cs-card-header">
                    <h2 class="cs-card-title">Case Activities</h2>
                </div>
                <div id="activityList">
                    <div class="cs-empty-state">
                        <div class="cs-empty-icon" aria-hidden="true">📋</div>
                        <div class="cs-empty-text">Loading…</div>
                    </div>
                </div>
            </div>

            <!-- Assets -->
            <div class="cs-card cs-full-width">
                <div class="cs-card-header">
                    <h2 class="cs-card-title">Assets</h2>
                </div>
                <div class="cs-table-container">
                    <table class="cs-table" role="table">
                        <caption class="sr-only">Assets in this case</caption>
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Name</th>
                                <th scope="col">Qty</th>
                                <th scope="col">Category</th>
                                <th scope="col">Sub Category</th>
                                <th scope="col">Condition</th>
                                <th scope="col">Location</th>
                                <th scope="col">Is Marital Asset</th>
                                <th scope="col">Purchase Date</th>
                                <th scope="col">Purchase Price</th>
                                <th scope="col">Sale Date</th>
                                <th scope="col">Sale Price</th>
                                <th scope="col">Created Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($items as $index => $item)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $item->name }}</td>
                                    <td>{{ $item->quantity }}</td>
                                    <td>{{ $item->category }}</td>
                                    <td>{{ $item->sub_category ?? '-' }}</td>
                                    <td>{{ $item->condition ?? '-' }}</td>
                                    <td>{{ $item->location ?? '-' }}</td>
                                    <td>{{ $item->is_marital_asset ? 'Yes' : 'No' }}</td>
                                    <td>{{ $item->purchase_date ? $item->purchase_date->format('Y-m-d') : '-' }}</td>
                                    <td>{{ $item->purchase_price ?? '-' }}</td>
                                    <td>{{ $item->sale_date ? $item->sale_date->format('Y-m-d') : '-' }}</td>
                                    <td>{{ $item->sale_price ?? '-' }}</td>
                                    <td>{{ $item->created_date ? $item->created_date->format('Y-m-d H:i') : '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="13">
                                        <div class="cs-empty-state">
                                            <div class="cs-empty-icon" aria-hidden="true">📦</div>
                                            <div class="cs-empty-text">No assets found</div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
var caseId = {{ $case->id }};

function getRoleClass(roleName, roleValue) {
    var rn = (roleName || '').toLowerCase();
    if (roleValue === 'LEGAL_RE' || rn.indexOf('employee') !== -1) return 'cs-role-employee';
    if (rn.indexOf('defendant') !== -1) return 'cs-role-defendant';
    if (rn.indexOf('plaintiff') !== -1) return 'cs-role-plaintiff';
    return 'cs-role-other';
}

function renderCaseUsersTable(caseUsers) {
    function esc(s) {
        if (s == null || s === undefined) return '';
        return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
    if (!caseUsers || !caseUsers.length) {
        $('#caseUsersTableWrapper').html('<p class="cs-empty-state cs-empty-text">No users assigned to this case.</p>');
        return;
    }
    var html = caseUsers.map(function(row) {
        var name = esc(row.user_name || 'N/A');
        var email = esc(row.user_email || 'N/A');
        var phone = esc(row.user_phone || 'N/A');
        var roleName = esc(row.role_name || 'Not Assigned');
        var roleClass = getRoleClass(row.role_name, row.role_value);
        return '<div class="cs-user-card" data-mapping-id="' + row.mapping_id + '">' +
            '<div><div class="cs-user-name">' + name + '</div><div class="cs-user-email">' + email + '</div></div>' +
            '<div class="cs-user-phone">' + phone + '</div>' +
            '<div><span class="cs-role-badge ' + roleClass + '">' + roleName + '</span></div></div>';
    }).join('');
    $('#caseUsersTableWrapper').html(html);
}

$(document).ready(function() {
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
                    html += '<div class="cs-table-container"><table class="cs-activity-table" role="table"><caption class="sr-only">Case activities</caption><thead><tr><th scope="col">Subject</th><th scope="col">Notes</th><th scope="col">Next Follow up date</th><th scope="col">Created By</th><th scope="col">Created date</th></tr></thead><tbody>';
                    res.activities.forEach(function(row) {
                        var userName = row.user_name || (row.user && row.user.name) || row.activity_by || 'N/A';
                        var subject = row.subject || '';
                        var notes = row.notes || '';
                        var nextDate = row.next_follow_up_date || '';
                        var created = row.created_date || '';
                        html += '<tr><td>' + escapeHtml(subject) + '</td><td>' + escapeHtml(notes) + '</td><td>' + escapeHtml(nextDate) + '</td><td>' + escapeHtml(userName) + '</td><td>' + escapeHtml(created) + '</td></tr>';
                    });
                    html += '</tbody></table></div>';
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
                    html = '<div class="cs-empty-state"><div class="cs-empty-icon" aria-hidden="true">📋</div><div class="cs-empty-text">No activities found.</div></div>';
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
});
</script>
@endpush
@endsection

@extends('backend.layout.inner-app')
@section('title', 'Cases | Share Fair')

@section('proxima')
<div class="cases-list-modern">
    @if (session('success'))
        <div class="alert alert-success" role="alert">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
    @endif

    <div class="page-header">
        <div class="header-content">
            <nav class="breadcrumb" aria-label="Breadcrumb">
                <a href="{{ route('admin.dashboard') }}">Dashboard</a>
                <span class="breadcrumb-separator" aria-hidden="true">/</span>
                <span class="breadcrumb-current">Cases</span>
            </nav>
        </div>
        @if(hasPermission('cases', 'create') || (isset($logUser) && $logUser->user_role_id == 'TENANT_A'))
        <a href="{{ route('admin.cases.create') }}" class="add-case-btn">Add New Case</a>
        @endif
    </div>

    <div class="filter-section filter-section-collapsible" id="filter-section">
        <button type="button" class="filter-toggle" id="filter-toggle" aria-expanded="false" aria-controls="filter-controls" aria-label="Show or hide filters">
            <span class="filter-toggle-label">
                <svg class="filter-icon-prefix" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                <span class="filter-toggle-text">Show filters</span>
            </span>
            <svg class="filter-toggle-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div id="filter-controls" class="filter-controls-wrap" hidden>
            <form method="GET" action="{{ route('admin.cases.index') }}" class="filter-controls" role="search" aria-label="Filter cases">
                <div class="filter-group">
                    <label class="filter-label" for="cases-search">Search</label>
                    <input type="text" id="cases-search" name="search" class="search-input" placeholder="Search case no / type / status..." value="{{ $search ?? '' }}" aria-label="Search case number, type, or status">
                </div>
                <div class="filter-group">
                    <label class="filter-label" for="cases-status-filter">Status Filter</label>
                    <select id="cases-status-filter" name="status" aria-label="Filter by status">
                        <option value="">-- Status Filter --</option>
                        @foreach ($caseStatuses as $status)
                            <option value="{{ $status->value }}" {{ (request('status') ?? '') == $status->value ? 'selected' : '' }}>{{ $status->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn-filter btn-primary">Filter</button>
                <a href="{{ route('admin.cases.index') }}" class="btn-filter btn-secondary">Reset</a>
            </form>
        </div>
    </div>

    <div class="table-container">
        <table>
            <caption class="sr-only">List of cases with case number, type, status, legal hold, created by, date, and actions</caption>
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col" class="sortable">
                        <a href="{{ route('admin.cases.index', array_merge(request()->all(), ['sort' => 'case_number', 'order' => ($sortField == 'case_number' && $sortOrder == 'asc') ? 'desc' : 'asc'])) }}">
                            Case Number {!! $sortField == 'case_number' ? ($sortOrder == 'asc' ? '↑' : '↓') : '' !!}
                        </a>
                    </th>
                    <th scope="col">
                        <a href="{{ route('admin.cases.index', array_merge(request()->all(), ['sort' => 'case_type_value', 'order' => ($sortField == 'case_type_value' && $sortOrder == 'asc') ? 'desc' : 'asc'])) }}" style="color:inherit;text-decoration:none;">
                            Case Type {!! $sortField == 'case_type_value' ? ($sortOrder == 'asc' ? '↑' : '↓') : '' !!}
                        </a>
                    </th>
                    <th scope="col">Status</th>
                    <th scope="col">Legal Hold</th>
                    <th scope="col">Created By</th>
                    <th scope="col" class="sortable">Date</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($cases as $index => $case)
                    @php
                        $statusClass = 'status-new';
                        $statusName = $case->caseStatus?->name ?? $case->case_status_value;
                        $sn = strtolower($statusName ?? '');
                        if (str_contains($sn, 'pending') || str_contains($sn, 'approval')) {
                            $statusClass = 'status-pending';
                        }
                    @endphp
                    <tr data-case-status="{{ $case->case_status_value ?? '' }}">
                        <td class="row-number">{{ $cases->firstItem() + $index }}</td>
                        <td>
                            <a href="{{ route('admin.cases.show', ['id' => $case->id]) }}" class="case-number">{{ $case->case_number }}</a>
                        </td>
                        <td class="case-type">{{ $case->caseType?->name ?? $case->case_type_value }}</td>
                        <td>
                            @if($case->caseStatus)
                                <span class="status-badge {{ $statusClass }}">
                                    <span class="status-dot" aria-hidden="true"></span>
                                    {{ $case->caseStatus->name }}
                                </span>
                            @else
                                <span class="status-badge status-new">{{ $case->case_status_value }}</span>
                            @endif
                        </td>
                        <td>
                            @if($case->is_legal_hold)
                                <span class="legal-hold-badge legal-hold-yes">Yes</span>
                            @else
                                <span class="legal-hold-no">No</span>
                            @endif
                        </td>
                        <td class="creator">{{ $case->createdBy->name ?? 'N/A' }}</td>
                        <td class="date">{{ \Carbon\Carbon::parse($case->created_date)->format('d-M-Y') }}</td>
                        <td>
                            <div class="actions actions-icons">
                                @if(($case->hasDistributionSummary()) && (hasPermission('cases', 'edit') || (isset($logUser) && $logUser->user_role_id == 'TENANT_A')))
                                <a href="{{ route('admin.cases.distribute.review', $case->id) }}" class="btn-action-icon btn-distribute" title="{{ $case->canLegalRepresentativeDistribute() ? 'Distribute assets' : 'Distribution summary' }}" aria-label="{{ $case->canLegalRepresentativeDistribute() ? 'Distribute assets for case' : 'View distribution summary for case' }} {{ $case->case_number }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 3h5v5"/><path d="M8 3H3v5"/><path d="M12 22V8"/><path d="m21 3-9 9"/><path d="M3 3l9 9"/><path d="M12 12a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/></svg>
                                </a>
                                @endif
                                @if(hasPermission('cases', 'edit') || (isset($logUser) && $logUser->user_role_id == 'TENANT_A'))
                                <a href="{{ route('admin.cases.edit', $case->id) }}" class="btn-action-icon btn-edit" title="Edit case" aria-label="Edit case {{ $case->case_number }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                                </a>
                                @endif
                                @if(hasPermission('cases', 'delete') || (isset($logUser) && $logUser->user_role_id == 'TENANT_A'))
                                <form action="{{ route('admin.cases.destroy', $case->id) }}" method="POST" class="actions-form" onsubmit="return confirm('Are you sure you want to delete this case? This action cannot be undone.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-action-icon btn-delete" title="Delete case" aria-label="Delete case {{ $case->case_number }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="empty-state">No cases found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($cases->hasPages())
            <div class="pagination-wrap">
                {{ $cases->appends(request()->except('page'))->links() }}
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var pageHeader = document.querySelector('.cases-list-modern .page-header');
    if (pageHeader) {
        var headerTop = pageHeader.getBoundingClientRect().top + window.pageYOffset;
        function updateStuck() {
            pageHeader.classList.toggle('is-stuck', window.scrollY > headerTop - 100);
        }
        window.addEventListener('scroll', updateStuck, { passive: true });
        updateStuck();
    }

    var filterSection = document.getElementById('filter-section');
    var filterToggle = document.getElementById('filter-toggle');
    var filterControls = document.getElementById('filter-controls');
    var filterControlsWrap = filterControls;

    if (filterToggle && filterControls) {
        var stored = localStorage.getItem('cases-filter-open');
        var isOpen = stored === 'true';
        if (isOpen) {
            filterControls.hidden = false;
            filterToggle.setAttribute('aria-expanded', 'true');
            filterToggle.querySelector('.filter-toggle-text').textContent = 'Hide filters';
            filterSection.classList.add('filter-section-open');
        }
        filterToggle.addEventListener('click', function() {
            isOpen = !filterControls.hidden;
            filterControls.hidden = isOpen;
            filterToggle.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
            filterToggle.querySelector('.filter-toggle-text').textContent = isOpen ? 'Show filters' : 'Hide filters';
            filterSection.classList.toggle('filter-section-open', !filterControls.hidden);
            localStorage.setItem('cases-filter-open', !filterControls.hidden);
        });
    }

    var filterForm = document.querySelector('.cases-list-modern .filter-controls');
    var statusFilter = document.getElementById('cases-status-filter');

    // Status filtering must hit the server (full dataset + pagination).
    // Client-side row hiding only sees the current page and misses matches on later pages.
    if (filterForm && statusFilter) {
        statusFilter.addEventListener('change', function () {
            filterForm.submit();
        });
    }
});
</script>
@endpush
@endsection

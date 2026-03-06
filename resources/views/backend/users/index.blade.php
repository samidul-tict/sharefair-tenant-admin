@extends('backend.layout.inner-app')
@section('title', 'Employee | Share Fair')

@section('proxima')
<div class="employees-list-modern">
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
                <span class="breadcrumb-current">Employees</span>
            </nav>
        </div>
        <a href="{{ route('admin.users.create') }}" class="add-case-btn">Add New Employee</a>
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
            <form method="GET" action="{{ route('admin.users.index') }}" class="filter-controls" role="search" aria-label="Filter employees">
                <div class="filter-group">
                    <label class="filter-label" for="users-search">Search</label>
                    <input type="text" id="users-search" name="search" class="search-input" placeholder="Search by name or email..." value="{{ $search ?? '' }}" aria-label="Search by name or email">
                </div>
                <div class="filter-group filter-group-spacer" aria-hidden="true">
                    <span class="filter-label">&nbsp;</span>
                    <span class="filter-spacer"></span>
                </div>
                <button type="submit" class="btn-filter btn-primary">Filter</button>
                <a href="{{ route('admin.users.index') }}" class="btn-filter btn-secondary">Reset</a>
            </form>
        </div>
    </div>

    <div class="table-container">
        <table>
            <caption class="sr-only">List of employees with name, email, phone, role, status, created by, date, and actions</caption>
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col" class="sortable">
                        <a href="{{ route('admin.users.index', array_merge(request()->all(), ['sort' => 'users.name', 'order' => ($sortField == 'users.name' && $sortOrder == 'asc') ? 'desc' : 'asc'])) }}">
                            Name {!! $sortField == 'users.name' ? ($sortOrder == 'asc' ? '↑' : '↓') : '' !!}
                        </a>
                    </th>
                    <th scope="col" class="sortable">
                        <a href="{{ route('admin.users.index', array_merge(request()->all(), ['sort' => 'users.email', 'order' => ($sortField == 'users.email' && $sortOrder == 'asc') ? 'desc' : 'asc'])) }}" style="color:inherit;text-decoration:none;">
                            Email {!! $sortField == 'users.email' ? ($sortOrder == 'asc' ? '↑' : '↓') : '' !!}
                        </a>
                    </th>
                    <th scope="col">Phone</th>
                    <th scope="col" class="sortable">
                        <a href="{{ route('admin.users.index', array_merge(request()->all(), ['sort' => 'roles.name', 'order' => ($sortField == 'roles.name' && $sortOrder == 'asc') ? 'desc' : 'asc'])) }}" style="color:inherit;text-decoration:none;">
                            Role {!! $sortField == 'roles.name' ? ($sortOrder == 'asc' ? '↑' : '↓') : '' !!}
                        </a>
                    </th>
                    <th scope="col">Status</th>
                    <th scope="col">Created By</th>
                    <th scope="col" class="sortable">
                        <a href="{{ route('admin.users.index', array_merge(request()->all(), ['sort' => 'users.created_date', 'order' => ($sortField == 'users.created_date' && $sortOrder == 'asc') ? 'desc' : 'asc'])) }}" style="color:inherit;text-decoration:none;">
                            Date {!! $sortField == 'users.created_date' ? ($sortOrder == 'asc' ? '↑' : '↓') : '' !!}
                        </a>
                    </th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $index => $user)
                    <tr>
                        <td class="row-number">{{ $users->firstItem() + $index }}</td>
                        <td>
                            <a href="{{ route('admin.users.show', $user->id) }}" class="case-number">{{ $user->name }}</a>
                        </td>
                        <td class="creator">{{ $user->email }}</td>
                        <td class="phone">{{ $user->phone_number ?? '—' }}</td>
                        <td class="case-type">{{ $user->role_name ?? '—' }}</td>
                        <td>
                            @if($user->is_active)
                                <span class="status-badge status-new"><span class="status-dot" aria-hidden="true"></span>Active</span>
                            @else
                                <span class="status-badge status-pending"><span class="status-dot" aria-hidden="true"></span>Inactive</span>
                            @endif
                        </td>
                        <td class="creator">{{ $user->created_by_name ?? 'N/A' }}</td>
                        <td class="date">{{ $user->created_date ? \Carbon\Carbon::parse($user->created_date)->format('d-M-Y') : '—' }}</td>
                        <td>
                            <div class="actions actions-icons">
                                <a href="{{ route('admin.users.edit', $user->id) }}" class="btn-action-icon btn-edit" title="Edit employee" aria-label="Edit {{ $user->name }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                                </a>
                                <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" class="actions-form" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-action-icon btn-delete" title="Delete employee" aria-label="Delete {{ $user->name }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="empty-state">No employees found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($users->hasPages())
            <div class="pagination-wrap">
                {{ $users->appends(request()->except('page'))->links() }}
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var pageHeader = document.querySelector('.employees-list-modern .page-header');
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

    if (filterToggle && filterControls) {
        var stored = localStorage.getItem('employees-filter-open');
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
            localStorage.setItem('employees-filter-open', !filterControls.hidden);
        });
    }

    var searchInput = document.getElementById('users-search');
    var table = document.querySelector('.employees-list-modern .table-container table');
    if (table && searchInput) {
        var rows = table.querySelectorAll('tbody tr');
        searchInput.addEventListener('input', function() {
            var searchTerm = (searchInput.value || '').toLowerCase();
            rows.forEach(function(row) {
                if (row.cells.length < 2) return;
                var name = (row.querySelector('.case-number') || row.cells[1]).textContent.toLowerCase();
                var email = (row.querySelector('.creator') || row.cells[2]).textContent.toLowerCase();
                var phone = (row.querySelector('.phone') || row.cells[3]).textContent.toLowerCase();
                var role = (row.querySelector('.case-type') || row.cells[4]).textContent.toLowerCase();
                var match = !searchTerm || name.includes(searchTerm) || email.includes(searchTerm) || phone.includes(searchTerm) || role.includes(searchTerm);
                row.style.display = match ? '' : 'none';
            });
        });
    }
});
</script>
@endpush
@endsection

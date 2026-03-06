@extends('backend.layout.inner-app')
@section('title', 'View Employee | Share Fair')
@section('proxima')

<div class="employee-show-modern">
    <div class="es-container">
        <nav class="es-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route('admin.dashboard') }}">Dashboard</a>
            <span class="es-breadcrumb-sep" aria-hidden="true">/</span>
            <a href="{{ route('admin.users.index') }}">Employees</a>
            <span class="es-breadcrumb-sep" aria-hidden="true">/</span>
            <span class="es-breadcrumb-current">View Employee</span>
        </nav>

        <div class="es-header">
            <div class="es-title-row">
                <div class="es-employee-name">{{ $user->name }}</div>
                @php
                    $roleLabel = $user->userRoleMappings->first()->role_value ?? 'Employee';
                @endphp
                <div class="es-role-badge">{{ $roleLabel }}</div>
                <div class="es-status-badge {{ $user->is_active ? 'es-status-active' : 'es-status-inactive' }}">
                    <span class="es-status-dot" aria-hidden="true"></span>
                    {{ $user->is_active ? 'Active' : 'Inactive' }}
                </div>
            </div>
            <a href="{{ route('admin.users.index') }}" class="es-btn-secondary">
                <i class="fas fa-arrow-left" aria-hidden="true"></i> Back to employees
            </a>
        </div>

        <div class="es-main-grid">
            <div class="es-card">
                <div class="es-card-header">
                    <h2 class="es-card-title">Employee Information</h2>
                </div>
                <div class="es-info-row">
                    <div class="es-info-label">Name</div>
                    <div class="es-info-value">{{ $user->name }}</div>
                </div>
                <div class="es-info-row">
                    <div class="es-info-label">Email</div>
                    <div class="es-info-value">{{ $user->email }}</div>
                </div>
                <div class="es-info-row">
                    <div class="es-info-label">Phone</div>
                    <div class="es-info-value">{{ $user->phone_number ?? '—' }}</div>
                </div>
                <div class="es-info-row">
                    <div class="es-info-label">Created Date</div>
                    <div class="es-info-value">{{ $user->created_date ? \Carbon\Carbon::parse($user->created_date)->format('d M Y H:i') : '—' }}</div>
                </div>
                <div class="es-info-row">
                    <div class="es-info-label">Created By</div>
                    <div class="es-info-value">{{ $user->createdByUser->name ?? '—' }}</div>
                </div>
            </div>

            <div class="es-card es-full-width">
                <div class="es-card-header">
                    <h2 class="es-card-title">Role & Tenant Mapping</h2>
                </div>
                <div class="es-table-container">
                    @if($user->userRoleMappings->count() > 0)
                        <table class="es-table" role="table">
                            <caption class="sr-only">Role and tenant assignments for this employee</caption>
                            <thead>
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">Role</th>
                                    <th scope="col">Tenant</th>
                                    <th scope="col">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($user->userRoleMappings as $index => $map)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $map->role_value ?? '—' }}</td>
                                        <td>{{ $map->tenant->name ?? '—' }}</td>
                                        <td>
                                            <span class="es-mapping-status {{ $map->is_active ? 'es-status-active' : 'es-status-inactive' }}">
                                                {{ $map->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="es-empty-state">
                            <div class="es-empty-icon" aria-hidden="true">👤</div>
                            <div class="es-empty-text">No roles assigned.</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

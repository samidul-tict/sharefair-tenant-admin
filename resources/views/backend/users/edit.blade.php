@extends('backend.layout.inner-app')
@section('title', 'Edit Employee | Share Fair')
@section('proxima')

<div class="employee-form-modern">
    @if (session('success'))
        <div class="alert alert-success" role="alert">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0 pl-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="page-header">
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route('admin.dashboard') }}">Dashboard</a>
            <span class="breadcrumb-separator" aria-hidden="true">/</span>
            <a href="{{ route('admin.users.index') }}">Employees</a>
            <span class="breadcrumb-separator" aria-hidden="true">/</span>
            <span class="breadcrumb-current">Edit Employee</span>
        </nav>
    </div>

    <div class="form-container">
        <div class="required-notice">Fields marked with an asterisk are required</div>

        <form method="POST" action="{{ route('admin.users.update', $user->id) }}" aria-label="Update employee">
            @csrf
            @method('PUT')

            <div class="form-row">
                <div class="form-group">
                    <label for="name">Name <span class="required-asterisk" aria-hidden="true">*</span></label>
                    <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required placeholder="Full name" autocomplete="name">
                </div>
                <div class="form-group">
                    <label for="email">Email <span class="required-asterisk" aria-hidden="true">*</span></label>
                    <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required placeholder="email@example.com" autocomplete="email">
                </div>
                <div class="form-group">
                    <label for="phone">Phone <span class="required-asterisk" aria-hidden="true">*</span></label>
                    <input type="tel" id="phone" name="phone" value="{{ old('phone', $user->phone_number) }}" required placeholder="Phone number" inputmode="tel">
                </div>
            </div>

            <div class="permissions-section">
                <h2 class="section-title">Menu Permissions</h2>
                <div class="quick-actions">
                    <button type="button" class="quick-action-btn" id="selectAllBtn">Select All</button>
                    <button type="button" class="quick-action-btn" id="deselectAllBtn">Deselect All</button>
                    <button type="button" class="quick-action-btn" id="selectViewOnlyBtn">View Only</button>
                </div>

                <div class="permissions-table-container">
                    <table class="permissions-table" role="table">
                        <caption class="sr-only">Set view, create, edit, delete, and upload permissions per menu</caption>
                        <thead>
                            <tr>
                                <th>Menu Name</th>
                                <th>View</th>
                                <th>Create</th>
                                <th>Edit</th>
                                <th>Delete</th>
                                <th>Upload</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($menus as $menu)
                            @php $p = $existingPermissionsFull[$menu->id] ?? null; @endphp
                            <tr>
                                <td>{{ $menu->menu_name }}</td>
                                <td>
                                    <div class="checkbox-wrapper">
                                        <input type="checkbox" class="custom-checkbox permission-checkbox" id="perm-view-{{ $menu->id }}" name="permissions[{{ $menu->id }}][view]" value="1" {{ ($p && $p->can_view) ? 'checked' : '' }} aria-label="View {{ $menu->menu_name }}">
                                        <label for="perm-view-{{ $menu->id }}" class="checkbox-label"></label>
                                    </div>
                                </td>
                                <td>
                                    <div class="checkbox-wrapper">
                                        <input type="checkbox" class="custom-checkbox permission-checkbox" id="perm-create-{{ $menu->id }}" name="permissions[{{ $menu->id }}][create]" value="1" {{ ($p && $p->can_create) ? 'checked' : '' }} aria-label="Create {{ $menu->menu_name }}">
                                        <label for="perm-create-{{ $menu->id }}" class="checkbox-label"></label>
                                    </div>
                                </td>
                                <td>
                                    <div class="checkbox-wrapper">
                                        <input type="checkbox" class="custom-checkbox permission-checkbox" id="perm-edit-{{ $menu->id }}" name="permissions[{{ $menu->id }}][edit]" value="1" {{ ($p && $p->can_edit) ? 'checked' : '' }} aria-label="Edit {{ $menu->menu_name }}">
                                        <label for="perm-edit-{{ $menu->id }}" class="checkbox-label"></label>
                                    </div>
                                </td>
                                <td>
                                    <div class="checkbox-wrapper">
                                        <input type="checkbox" class="custom-checkbox permission-checkbox" id="perm-delete-{{ $menu->id }}" name="permissions[{{ $menu->id }}][delete]" value="1" {{ ($p && $p->can_delete) ? 'checked' : '' }} aria-label="Delete {{ $menu->menu_name }}">
                                        <label for="perm-delete-{{ $menu->id }}" class="checkbox-label"></label>
                                    </div>
                                </td>
                                <td>
                                    <div class="checkbox-wrapper">
                                        <input type="checkbox" class="custom-checkbox permission-checkbox" id="perm-upload-{{ $menu->id }}" name="permissions[{{ $menu->id }}][upload]" value="1" {{ ($p && $p->can_upload) ? 'checked' : '' }} aria-label="Upload {{ $menu->menu_name }}">
                                        <label for="perm-upload-{{ $menu->id }}" class="checkbox-label"></label>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Update Employee</button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var selectAllBtn = document.getElementById('selectAllBtn');
    var deselectAllBtn = document.getElementById('deselectAllBtn');
    var selectViewOnlyBtn = document.getElementById('selectViewOnlyBtn');
    var allCheckboxes = document.querySelectorAll('.employee-form-modern .permission-checkbox');

    if (selectAllBtn) selectAllBtn.addEventListener('click', function() {
        allCheckboxes.forEach(function(cb) { cb.checked = true; });
    });
    if (deselectAllBtn) deselectAllBtn.addEventListener('click', function() {
        allCheckboxes.forEach(function(cb) { cb.checked = false; });
    });
    if (selectViewOnlyBtn) selectViewOnlyBtn.addEventListener('click', function() {
        allCheckboxes.forEach(function(cb) {
            cb.checked = cb.id.indexOf('-view') !== -1;
        });
    });
});
</script>
@endpush
@endsection

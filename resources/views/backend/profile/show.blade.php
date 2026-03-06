@extends('backend.layout.inner-app')
@section('title', 'Profile | Share Fair')
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
            <span class="breadcrumb-current">Profile</span>
        </nav>
    </div>

    <div class="form-container">
        <div class="required-notice">Fields marked with an asterisk are required</div>

        <form method="POST" action="{{ route('admin.profile.update') }}" aria-label="Update your profile">
            @csrf
            @method('PUT')

            <div class="form-row">
                <div class="form-group">
                    <label for="name">Name <span class="required-asterisk" aria-hidden="true">*</span></label>
                    <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required aria-required="true" placeholder="Full name" autocomplete="name">
                </div>
                <div class="form-group">
                    <label for="profile-email">Email</label>
                    <input type="email" id="profile-email" value="{{ $user->email }}" readonly aria-readonly="true" class="profile-email-readonly" aria-label="Email (read-only)" autocomplete="email">
                </div>
                <div class="form-group">
                    <label for="phone_number">Phone</label>
                    <input type="tel" id="phone_number" name="phone_number" value="{{ old('phone_number', $user->phone_number) }}" placeholder="Phone number" inputmode="tel" autocomplete="tel" aria-label="Phone number">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="profile-role">Role</label>
                    <div id="profile-role" class="form-control-plaintext" style="padding: 0.6rem 0; font-weight: 600;" aria-describedby="profile-role-hint">{{ $roleName ?? '—' }}</div>
                    <span id="profile-role-hint" class="text-muted small">Your role is assigned by an administrator.</span>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save changes</button>
                <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

@endsection

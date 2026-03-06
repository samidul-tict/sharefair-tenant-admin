@extends('backend.layout.app')
@section('title', 'Share Fair')
@section('login')

<div class="card card-primary">
    <div class="card-header"><h2 class="h4 mb-0">Login</h2></div>
    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success" role="alert">{{ session('success') }}</div>
        @endif

        @if(session('otp_sent_to'))
            {{-- Step 2: Enter OTP --}}
            <p class="text-muted mb-3">We sent a one-time code to <strong>{{ session('otp_sent_to') }}</strong></p>
            <form method="POST" action="{{ route('admin.login.post') }}" class="needs-validation" novalidate="" aria-label="Verify OTP form">
                @csrf
                <input type="hidden" name="email" value="{{ old('email', session('otp_sent_to')) }}">
                <div class="form-group mb-3">
                    <label for="otp">One-time code <span class="text-danger" aria-hidden="true">*</span></label>
                    <input
                        id="otp"
                        type="text"
                        inputmode="numeric"
                        pattern="[0-9]*"
                        maxlength="6"
                        class="form-control @error('otp') is-invalid @enderror"
                        name="otp"
                        value="{{ old('otp') }}"
                        tabindex="1"
                        required
                        autofocus
                        autocomplete="one-time-code"
                        placeholder="000000"
                        aria-required="true"
                        aria-invalid="{{ $errors->has('otp') ? 'true' : 'false' }}"
                        aria-describedby="{{ $errors->has('otp') ? 'otp-error' : 'otp-hint' }}">
                    @error('otp')
                        <div id="otp-error" class="invalid-feedback d-block" role="alert">{{ $message }}</div>
                    @else
                        <div id="otp-hint" class="form-text">Enter the 6-digit code from your email</div>
                    @enderror
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-lg btn-block" tabindex="2">Verify & sign in</button>
                </div>
            </form>
            <div class="mt-3 text-center">
                <a href="{{ route('admin.login') }}?reset=1" class="text-small">Use a different email</a>
            </div>
        @else
            {{-- Step 1: Request OTP --}}
            <form method="POST" action="{{ route('admin.request-otp') }}" class="needs-validation" novalidate="" aria-label="Request OTP form">
                @csrf
                <div class="form-group mb-3">
                    <label for="email">Email <span class="text-danger" aria-hidden="true">*</span></label>
                    <input
                        id="email"
                        type="email"
                        class="form-control @error('email') is-invalid @enderror"
                        name="email"
                        value="{{ old('email') }}"
                        tabindex="1"
                        required
                        autofocus
                        autocomplete="email"
                        aria-required="true"
                        aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
                        aria-describedby="{{ $errors->has('email') ? 'email-error' : 'email-hint' }}">
                    @error('email')
                        <div id="email-error" class="invalid-feedback d-block" role="alert">{{ $message }}</div>
                    @else
                        <div id="email-hint" class="form-text">We’ll send a one-time code to this address</div>
                    @enderror
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-lg btn-block" tabindex="2">Send one-time code</button>
                </div>
            </form>
        @endif
    </div>
</div>

@endsection

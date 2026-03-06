@extends('backend.layout.inner-app')
@section('title', 'Reset Password | Share Fair')
@section('proxima')

<div class="reset-password-page">
    <div class="rp-container">
        @if (session('success'))
            <div class="alert alert-success" role="alert">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger rp-error-message active" role="alert">{{ session('error') }}</div>
        @endif

        <nav class="rp-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route('admin.dashboard') }}">Dashboard</a>
            <span class="rp-breadcrumb-separator" aria-hidden="true">/</span>
            <span class="rp-breadcrumb-current">Reset Password</span>
        </nav>
        <p class="rp-page-description">Set a new password for your account.</p>

        <div class="rp-form-card">
            <div class="rp-error-message" id="rpErrorMessage" role="alert" aria-live="polite">
                Passwords do not match. Please try again.
            </div>

            @if($errors->any())
                <div class="rp-error-message active" role="alert">
                    @foreach($errors->all() as $error)
                        {{ $error }}
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('admin.users.reset-password.submit', $user->id) }}" id="rpResetPasswordForm" aria-label="Reset password">
                @csrf
                @method('PUT')

                <div class="rp-form-row">
                    <div class="rp-form-group">
                        <label for="password">
                            New Password
                            <span class="rp-required-asterisk" aria-hidden="true">*</span>
                        </label>
                        <div class="rp-input-wrapper">
                            <input type="password" id="password" name="password" placeholder="Min. 6 characters" required minlength="6" autocomplete="new-password">
                            <button type="button" class="rp-toggle-password" data-target="password" aria-label="Toggle password visibility">👁️</button>
                        </div>
                        <div class="rp-password-strength" id="rpPasswordStrength">
                            <div class="rp-strength-bar">
                                <div class="rp-strength-fill"></div>
                            </div>
                            <div class="rp-strength-text"></div>
                        </div>
                    </div>

                    <div class="rp-form-group">
                        <label for="password_confirmation">
                            Confirm Password
                            <span class="rp-required-asterisk" aria-hidden="true">*</span>
                        </label>
                        <div class="rp-input-wrapper">
                            <input type="password" id="password_confirmation" name="password_confirmation" placeholder="Re-enter password" required minlength="6" autocomplete="new-password">
                            <button type="button" class="rp-toggle-password" data-target="password_confirmation" aria-label="Toggle password visibility">👁️</button>
                        </div>
                    </div>
                </div>

                <div class="rp-password-requirements">
                    <div class="rp-requirements-title">Password Requirements</div>
                    <ul class="rp-requirement-list">
                        <li class="rp-requirement-item" data-requirement="length">At least 6 characters</li>
                        <li class="rp-requirement-item" data-requirement="uppercase">One uppercase letter</li>
                        <li class="rp-requirement-item" data-requirement="lowercase">One lowercase letter</li>
                        <li class="rp-requirement-item" data-requirement="number">One number</li>
                    </ul>
                </div>

                <div class="rp-form-actions">
                    <button type="submit" class="rp-btn rp-btn-primary" id="rpSubmitBtn">Reset Password</button>
                    <a href="{{ route('admin.dashboard') }}" class="rp-btn rp-btn-secondary" id="rpCancelBtn">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.rp-toggle-password').forEach(function(button) {
        button.addEventListener('click', function() {
            var targetId = this.getAttribute('data-target');
            var input = document.getElementById(targetId);
            if (input.type === 'password') {
                input.type = 'text';
                this.textContent = '🙈';
            } else {
                input.type = 'password';
                this.textContent = '👁️';
            }
        });
    });

    var newPasswordInput = document.getElementById('password');
    var confirmPasswordInput = document.getElementById('password_confirmation');
    var passwordStrength = document.getElementById('rpPasswordStrength');
    var strengthText = passwordStrength ? passwordStrength.querySelector('.rp-strength-text') : null;
    var form = document.getElementById('rpResetPasswordForm');
    var errorMessage = document.getElementById('rpErrorMessage');
    var submitBtn = document.getElementById('rpSubmitBtn');

    function updateRequirements(password) {
        var requirements = {
            length: password.length >= 6,
            uppercase: /[A-Z]/.test(password),
            lowercase: /[a-z]/.test(password),
            number: /[0-9]/.test(password)
        };
        Object.keys(requirements).forEach(function(key) {
            var item = document.querySelector('.rp-requirement-item[data-requirement="' + key + '"]');
            if (item) {
                if (requirements[key]) item.classList.add('met');
                else item.classList.remove('met');
            }
        });
    }

    if (newPasswordInput && passwordStrength) {
        newPasswordInput.addEventListener('input', function() {
            var password = this.value;
            if (password.length === 0) {
                passwordStrength.classList.remove('active');
                return;
            }
            passwordStrength.classList.add('active');
            var strength = 0;
            if (password.length >= 6) strength++;
            if (password.match(/[A-Z]/)) strength++;
            if (password.match(/[a-z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            passwordStrength.classList.remove('strength-weak', 'strength-medium', 'strength-strong');
            if (strength <= 2) {
                passwordStrength.classList.add('strength-weak');
                if (strengthText) strengthText.textContent = 'Weak password';
            } else if (strength === 3) {
                passwordStrength.classList.add('strength-medium');
                if (strengthText) strengthText.textContent = 'Medium password';
            } else {
                passwordStrength.classList.add('strength-strong');
                if (strengthText) strengthText.textContent = 'Strong password';
            }
            updateRequirements(password);
        });
    }

    if (form && errorMessage) {
        form.addEventListener('submit', function(e) {
            var newPassword = newPasswordInput.value;
            var confirmPassword = confirmPasswordInput.value;
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                errorMessage.textContent = 'Passwords do not match. Please try again.';
                errorMessage.classList.add('active');
                confirmPasswordInput.style.borderColor = 'var(--rp-danger)';
                return;
            }
            if (newPassword.length < 6) {
                e.preventDefault();
                errorMessage.textContent = 'Password must be at least 6 characters.';
                errorMessage.classList.add('active');
                return;
            }
            errorMessage.classList.remove('active');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Resetting...';
            }
        });
    }

    if (confirmPasswordInput && errorMessage) {
        confirmPasswordInput.addEventListener('input', function() {
            errorMessage.classList.remove('active');
            this.style.borderColor = '';
        });
    }

    [newPasswordInput, confirmPasswordInput].forEach(function(input) {
        if (!input) return;
        input.addEventListener('blur', function() {
            if (this.value.trim() === '') this.style.borderColor = 'var(--rp-danger)';
            else this.style.borderColor = 'var(--rp-success)';
        });
        input.addEventListener('focus', function() {
            this.style.borderColor = 'var(--rp-border)';
        });
    });
});
</script>
@endpush
@endsection

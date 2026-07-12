<?php

namespace App\Http\Controllers\backend;

use App\Http\Controllers\Controller;
use App\Mail\OtpMail;
use App\Models\Otp;
use App\Models\User;
use App\Models\UserRoleMapping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    public function index(Request $request)
    {
        if ($request->query('reset') === '1') {
            $request->session()->forget('otp_sent_to');
        }
        return view('backend.login', [
            'otpSentTo' => $request->session()->get('otp_sent_to'),
        ]);
    }

    /**
     * Request OTP: validate email, ensure user exists with allowed role, create OTP, send email.
     */
    public function requestOtp(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ], [], ['email' => 'email address']);

        $email = $validated['email'];

        $user = User::where('email', $email)->first();
        if (!$user) {
            return back()->withErrors(['email' => 'No account found with this email.'])->withInput($request->only('email'));
        }

        $hasRole = UserRoleMapping::where('user_id', $user->id)
            ->whereIn('role_value', ['TENANT_A', 'EMP'])
            ->where('is_active', true)
            ->exists();

        if (!$hasRole) {
            return back()->withErrors(['email' => 'Access denied. You do not have the required role.'])->withInput($request->only('email'));
        }

        $otpCode = (string) random_int(100000, 999999);
        $expiresAt = now()->addMinutes(15);

        Otp::create([
            'email' => $email,
            'otp_code' => $otpCode,
            'expires_at' => $expiresAt,
            'is_used' => false,
            'created_at' => now(),
        ]);

        Mail::to($email)->send(new OtpMail($otpCode, $email));

        $request->session()->put('otp_sent_to', $email);

        return redirect()->route('admin.login')->with('success', 'A one-time code has been sent to your email. Enter it below to sign in.');
    }

    /**
     * Verify OTP and log the user in.
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'string', 'size:6'],
        ], [], ['email' => 'email address', 'otp' => 'one-time code']);

        $email = $validated['email'];
        $otpCode = $validated['otp'];

        $otp = Otp::where('email', $email)
            ->where('otp_code', $otpCode)
            ->where('is_used', false)
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (!$otp) {
            return back()->withErrors(['otp' => 'Invalid or expired code. Request a new one if needed.'])->withInput($request->only('email'));
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            return back()->withErrors(['email' => 'User not found.'])->withInput($request->only('email'));
        }

        $roleMapping = UserRoleMapping::where('user_id', $user->id)
            ->whereIn('role_value', ['TENANT_A', 'EMP'])
            ->where('is_active', true)
            ->first();

        if (!$roleMapping) {
            return back()->withErrors(['email' => 'Access denied. You do not have the required role.'])->withInput($request->only('email'));
        }

        $apiBaseUrl = rtrim((string) config('services.sharefair.api_base_url'), '/');
        $timeout = (int) config('services.sharefair.timeout', 15);

        try {
            $apiResponse = Http::withHeaders([
                'x-tenant-id' => (string) ($roleMapping->tenant_id ?? 1),
                'x-role-value' => $roleMapping->role_value,
            ])->acceptJson()
                ->timeout($timeout)
                ->post("{$apiBaseUrl}/auth/verify-otp", [
                    'email' => $email,
                    'otp' => $otpCode,
                ]);
        } catch (\Illuminate\Http\Client\ConnectionException) {
            return back()->withErrors(['otp' => 'Unable to reach Share Fair API. Please try again.'])->withInput($request->only('email'));
        }

        if (!$apiResponse->successful()) {
            $apiMessage = $apiResponse->json('message') ?? $apiResponse->json('detail') ?? 'Unable to authenticate with Share Fair API.';
            if (is_array($apiMessage)) {
                $apiMessage = 'Unable to authenticate with Share Fair API.';
            }

            return back()->withErrors(['otp' => $apiMessage])->withInput($request->only('email'));
        }

        $accessToken = $apiResponse->json('data.access_token');
        if (!$accessToken) {
            return back()->withErrors(['otp' => 'Authentication succeeded but no API token was returned.'])->withInput($request->only('email'));
        }

        $otp->update(['is_used' => true]);

        Auth::login($user, false);
        $request->session()->regenerate();
        $request->session()->put('sharefair_api_token', $accessToken);
        $request->session()->forget('otp_sent_to');

        return redirect()->intended(route('admin.dashboard'))->with('success', 'Login successful!');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->forget('sharefair_api_token');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}

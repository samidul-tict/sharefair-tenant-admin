<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Your login code</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f4f6f8; margin: 0; padding: 0; }
        .email-container { max-width: 480px; margin: 40px auto; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.08); }
        .header { background: #004aad; color: white; padding: 20px; text-align: center; }
        .header h1 { margin: 0; font-size: 20px; font-weight: 700; }
        .content { padding: 28px; }
        .content p { font-size: 15px; color: #333; line-height: 1.6; margin: 0 0 16px; }
        .otp-box { background: #f0f4ff; border: 2px dashed #004aad; padding: 16px; text-align: center; border-radius: 8px; margin: 20px 0; }
        .otp-code { font-size: 28px; font-weight: 700; letter-spacing: 6px; color: #004aad; font-family: monospace; }
        .footer { background: #f1f1f1; padding: 14px; font-size: 12px; color: #555; text-align: center; }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>{{ config('app.name', 'Share Fair') }}</h1>
        </div>
        <div class="content">
            <p>Use this one-time code to sign in:</p>
            <div class="otp-box">
                <span class="otp-code">{{ $otpCode }}</span>
            </div>
            <p>This code expires in 15 minutes. If you didn't request it, you can ignore this email.</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} {{ config('app.name', 'Share Fair') }}. This is an automated message.
        </div>
    </div>
</body>
</html>

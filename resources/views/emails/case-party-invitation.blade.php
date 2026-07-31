<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Share Fair case invitation</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f4f6f8; margin: 0; padding: 0; }
        .email-container { max-width: 560px; margin: 40px auto; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.08); }
        .header { background: #004aad; color: white; padding: 20px; text-align: center; }
        .header h1 { margin: 0; font-size: 20px; font-weight: 700; }
        .content { padding: 28px; }
        .content p { font-size: 15px; color: #333; line-height: 1.6; margin: 0 0 16px; }
        .details { background: #f8faff; border: 1px solid #dbeafe; border-radius: 8px; padding: 16px 18px; margin: 20px 0; }
        .details p { margin: 0 0 8px; font-size: 14px; }
        .details p:last-child { margin-bottom: 0; }
        .btn-wrap { text-align: center; margin: 24px 0 8px; }
        .btn { display: inline-block; background: #004aad; color: #fff !important; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-weight: 700; font-size: 15px; }
        .footer { background: #f1f1f1; padding: 14px; font-size: 12px; color: #555; text-align: center; }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>{{ config('app.name', 'Share Fair') }}</h1>
        </div>
        <div class="content">
            <p>Hello {{ $recipientName }},</p>
            <p>
                <strong>{{ $legalCounselName }}</strong> has added you to case
                <strong>{{ $caseNumber }}</strong> on Share Fair.
            </p>

            @if($clientName || $spouseName)
                <div class="details">
                    <p><strong>Matter parties</strong></p>
                    @if($clientName)
                        <p>Client: {{ $clientName }}</p>
                    @endif
                    @if($spouseName)
                        <p>Spouse: {{ $spouseName }}</p>
                    @endif
                </div>
            @endif

            <p>Your role on this case: <strong>{{ $roleLabel }}</strong></p>
            <p>Join Share Fair to access the case, collaborate with counsel, and participate in the asset distribution process.</p>

            <div class="btn-wrap">
                <a href="{{ $joinUrl }}" class="btn">Join Share Fair</a>
            </div>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} {{ config('app.name', 'Share Fair') }}. This is an automated message.
        </div>
    </div>
</body>
</html>

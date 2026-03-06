<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>New Enquiry</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background-color: #f4f6f8;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 620px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }
        .header {
            background: #004aad;
            color: white;
            padding: 25px 30px;
            text-align: center;
        }
        .header img {
            max-height: 60px;
            margin-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .content {
            padding: 30px;
        }
        .content h2 {
            color: #004aad;
            font-size: 20px;
            margin-bottom: 10px;
        }
        .content p {
            font-size: 15px;
            color: #333;
            line-height: 1.6;
            margin-bottom: 10px;
        }
        .content strong {
            color: #000;
        }
        .highlight {
            background: #f9f9f9;
            border-left: 4px solid #004aad;
            padding: 10px 15px;
            border-radius: 5px;
        }
        .divider {
            border-top: 1px solid #ddd;
            margin: 25px 0;
        }
        .thank-you {
            background: #e8f0fe;
            padding: 15px;
            border-radius: 8px;
            color: #00357b;
            font-weight: 500;
            font-size: 15px;
        }
        .footer {
            background: #f1f1f1;
            padding: 18px 25px;
            font-size: 13px;
            color: #555;
            text-align: center;
        }
        .footer a {
            color: #004aad;
            text-decoration: none;
            font-weight: 500;
        }
        .footer p {
            margin: 4px 0;
        }
    </style>
</head>
<body>

    <div class="email-container">
        <div class="header">
            <img src="https://parkinsonandneurology.com/frontend-assets/images/logo.png" alt="Clinic Logo">
            <h1>Parkinson & Neurology Clinic</h1>
        </div>

        <div class="content">
            <h2>🩺 New Enquiry Submitted</h2>

            <p><strong>Name:</strong> {{ $data['firstName'] }} {{ $data['lastName'] }}</p>
            <p><strong>Email:</strong> <a href="mailto:{{ $data['email'] }}">{{ $data['email'] }}</a></p>
            <p><strong>Phone:</strong> {{ $data['phone'] }}</p>
            <p><strong>Service:</strong> {{ $data['service'] ?? 'N/A' }}</p>
            <p><strong>Message:</strong></p>
            <div class="highlight">
                {{ $data['message'] ?? 'No message provided.' }}
            </div>

            <div class="divider"></div>

            <div class="thank-you">
                ✅ Thank you! You have successfully enquired with your doctor.<br>
                Our team will call you within the next <strong>24 hours</strong>.
            </div>
        </div>

        <div class="footer">
            <p><em>This is an automated message. Replies to this email are not monitored.</em></p>
            <p>For assistance, contact our 
                <a href="mailto:parkinsonsandneurology@gmail.com">Support Team</a>.
            </p>
            <p>&copy; {{ date('Y') }} Parkinson & Neurology Clinic. All rights reserved.</p>
        </div>
    </div>

</body>
</html>

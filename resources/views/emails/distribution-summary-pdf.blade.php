<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Distribution Summary</title>
</head>
<body>
    <p>Hello {{ $recipientName }},</p>

    <p>
        The distribution summary PDF for case <strong>{{ $case->case_number }}</strong>
        is attached to this email.
    </p>

    <p>Thank you,<br>Share Fair</p>
</body>
</html>

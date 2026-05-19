<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Access your account</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #007bff; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f9f9f9; }
        .button { display: inline-block; padding: 12px 24px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin: 20px 0; }
        .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
        .warning { background: #fff3cd; padding: 10px; border-radius: 4px; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Access your account</h1>
        </div>
        <div class="content">
            <p>Hello <strong>{{ $customer->first_name ?: $customer->email }}</strong>,</p>
            <p>You received this link to access your Customer Portal account. This is a single-use link that expires in <strong>15 minutes</strong>.</p>
            <p style="text-align: center;">
                <a href="{{ $magicLinkUrl }}" class="button">Access my account</a>
            </p>
            <p>If you didn't request this link, you can safely ignore this email.</p>
            <div class="warning">
                <strong>Note:</strong> This link will expire in 15 minutes for security reasons. If it expires, you'll need to request a new one from the login page.
            </div>
        </div>
        <div class="footer">
            <p>This is an automated email from the Customer Portal. Do not reply to this message.</p>
        </div>
    </div>
</body>
</html>
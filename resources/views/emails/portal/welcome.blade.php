<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Welcome to the Portal</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #28a745; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f9f9f9; }
        .button { display: inline-block; padding: 12px 24px; background: #28a745; color: white; text-decoration: none; border-radius: 4px; margin: 20px 0; }
        .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Welcome to the Portal</h1>
        </div>
        <div class="content">
            <p>Hola <strong>{{ $customer->first_name ?: $customer->email }}</strong>,</p>
            <p>Your account on the Customer Portal has been created successfully. Now you can:</p>
            <ul>
                <li>View the status of your requests</li>
                <li>Check your orders and returns</li>
                <li>Review your credit balance</li>
            </ul>
            <p style="text-align: center;">
                <a href="{{ $portalUrl }}" class="button">Access the Portal</a>
            </p>
            <p>If you have any questions, feel free to contact us.</p>
        </div>
        <div class="footer">
            <p>This is an automated email from the Customer Portal. Do not reply to this message.</p>
        </div>
    </div>
</body>
</html>
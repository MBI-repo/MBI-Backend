<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Welcome</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; color: #222; }
        .container { max-width: 600px; margin: 0 auto; padding: 24px; }
        .btn { display: inline-block; background: #0d6efd; color: #fff; padding: 10px 16px; text-decoration: none; border-radius: 6px; }
    </style>
    </head>
<body>
    <div class="container">
        <h2>Welcome to MyBridge International</h2>
        <p>Hi {{ $user->full_name ?? 'there' }},</p>
        <p>
            Your account has been created successfully. We’re excited to have you on board!
        </p>
        <p>
            You can now log in and start connecting with professionals, participate in conversations,
            and explore upcoming events.
        </p>
        <p>
            If you have any questions, reply to this email—we’re here to help.
        </p>
        <p style="margin-top: 24px;">
            Cheers,<br />
            The MyBridge Team
        </p>
    </div>
</body>
</html>


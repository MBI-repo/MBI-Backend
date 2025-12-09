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
        <h2>Welcome to My Bridge International</h2>
        <p>Hi {{ $user->full_name ?? 'there' }},</p>
        <p>
            Your account has been created successfully. Welcome to My Bridge International!
        </p>
        <p>
            You can now log in to connect with professionals, join conversations, and explore upcoming events across our global community.


        </p>
        <p>
    If you ever need support, reply to this email. We're here to help.
   
    </p>
        <p style="margin-top: 24px;">
            Warm regards,<br />
            The My Bridge Team
        </p>
    </div>
</body>
</html>


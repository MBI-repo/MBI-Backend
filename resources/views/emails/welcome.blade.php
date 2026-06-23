<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Welcome to My Bridge International</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            width: 100% !important;
            height: 100% !important;
            background-color: #f8fafc;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #334155;
            -webkit-font-smoothing: antialiased;
        }
        table {
            border-collapse: collapse;
        }
        .wrapper {
            width: 100%;
            table-layout: fixed;
            background-color: #f8fafc;
            padding: 40px 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.025);
            overflow: hidden;
        }
        .header {
            background-color: #2563eb;
            padding: 32px;
            text-align: center;
        }
        .header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -0.025em;
        }
        .content {
            padding: 32px;
        }
        .greeting {
            font-size: 20px;
            font-weight: 700;
            color: #0f172a;
            margin-top: 0;
            margin-bottom: 16px;
        }
        .text {
            font-size: 16px;
            line-height: 1.625;
            color: #475569;
            margin-bottom: 24px;
        }
        .highlight-box {
            background-color: #f1f5f9;
            border-left: 4px solid #2563eb;
            padding: 16px;
            border-radius: 0 8px 8px 0;
            margin-bottom: 24px;
        }
        .highlight-box p {
            margin: 0;
            font-size: 15px;
            line-height: 1.5;
            color: #334155;
        }
        .btn-container {
            text-align: center;
            margin: 32px 0 16px;
        }
        .btn {
            display: inline-block;
            background-color: #2563eb;
            color: #ffffff !important;
            padding: 14px 28px;
            text-decoration: none;
            border-radius: 9999px;
            font-weight: 600;
            font-size: 15px;
            box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2);
            transition: background-color 0.2s;
        }
        .footer {
            padding: 0 32px 32px;
            border-top: 1px solid #f1f5f9;
            font-size: 14px;
            color: #64748b;
        }
        .footer-note {
            margin-top: 20px;
            font-size: 13px;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <table class="wrapper" width="100%">
        <tr>
            <td align="center">
                <table class="container" width="100%">
                    <!-- Header Banner -->
                    <tr>
                        <td class="header">
                            <h1>My Bridge International</h1>
                        </td>
                    </tr>
                    
                    <!-- Content Area -->
                    <tr>
                        <td class="content">
                            <p class="greeting">Hello {{ $user->full_name ?? 'there' }},</p>
                            
                            <p class="text">
                                Your account has been successfully created! Welcome to the <strong>My Bridge International</strong> platform. We are thrilled to have you join our global healthcare and professional community.
                            </p>
                            
                            <div class="highlight-box">
                                <p>
                                    You can now log in to connect with certified medical professionals, manage your consultations and appointments, view lab orders, and keep track of your telemedicine session history securely.
                                </p>
                            </div>
                            
                            <p class="text">
                                Click the button below to sign in to your dashboard and complete any remaining profile setups.
                            </p>
                            
                            <div class="btn-container">
                                <a href="https://portal.mybridgeinternational.org" class="btn">Access Your Portal</a>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Footer Info -->
                    <tr>
                        <td class="footer">
                            <p class="text" style="margin-bottom: 12px;">
                                Warm regards,<br />
                                <strong>The My Bridge Team</strong>
                            </p>
                            <p class="footer-note">
                                If you did not register for this account or if you need any assistance, please don't hesitate to reply to this email. We are always here to help.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

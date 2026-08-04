<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Verify Your Email — My Bridge International</title>
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
        table { border-collapse: collapse; }
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
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.025);
            overflow: hidden;
        }

        /* Header — matching medical_welcome gradient */
        .header {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            padding: 40px 32px;
            text-align: center;
        }
        .header h1 {
            color: #ffffff;
            margin: 0 0 6px;
            font-size: 26px;
            font-weight: 800;
            letter-spacing: -0.025em;
        }
        .header-subtitle {
            color: rgba(255,255,255,0.9);
            margin: 0;
            font-size: 15px;
        }

        /* Logo */
        .logo-box {
            display: inline-block;
            background: rgba(255,255,255,0.15);
            border-radius: 12px;
            padding: 10px 18px;
            margin-bottom: 20px;
        }
        .logo-text {
            color: #ffffff;
            font-size: 20px;
            font-weight: 900;
            letter-spacing: 0.05em;
            margin: 0;
        }

        /* Icon ring */
        .icon-ring {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }

        /* Body */
        .body {
            padding: 36px 32px;
        }
        .greeting {
            font-size: 20px;
            font-weight: 700;
            color: #1e293b;
            margin: 0 0 12px;
        }
        .body p {
            font-size: 15px;
            line-height: 1.7;
            color: #475569;
            margin: 0 0 16px;
        }

        /* CTA Button */
        .cta-wrap {
            text-align: center;
            margin: 32px 0;
        }
        .cta-btn {
            display: inline-block;
            padding: 14px 36px;
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 700;
            letter-spacing: 0.01em;
        }

        /* Fallback URL box */
        .url-box {
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 14px 16px;
            margin: 16px 0 24px;
            word-break: break-all;
            font-size: 13px;
            color: #1e40af;
        }

        /* Divider */
        .divider {
            border: none;
            border-top: 1px solid #e2e8f0;
            margin: 28px 0;
        }

        /* Note */
        .note {
            background: #eff6ff;
            border-left: 4px solid #3b82f6;
            border-radius: 4px;
            padding: 12px 16px;
            font-size: 14px;
            color: #1e40af;
            margin: 0 0 24px;
        }

        /* Footer */
        .footer {
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 24px 32px;
            text-align: center;
        }
        .footer p {
            font-size: 13px;
            color: #94a3b8;
            margin: 0 0 4px;
        }
        .footer a {
            color: #3b82f6;
            text-decoration: none;
        }
        .expiry-note {
            font-size: 13px;
            color: #94a3b8;
            text-align: center;
            margin: 0 0 8px;
        }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="container">

        <!-- Header -->
        <div class="header">
            <div class="logo-box">
                <p class="logo-text">MBI</p>
            </div>
            <!-- Email icon as text/emoji for email client compatibility -->
            <div style="font-size:48px; margin-bottom:16px;">✉️</div>
            <h1>Verify Your Email</h1>
            <p class="header-subtitle">One quick step to activate your account</p>
        </div>

        <!-- Body -->
        <div class="body">
            <p class="greeting">Hi {{ $user->full_name }},</p>

            <p>
                Thank you for joining <strong>My Bridge International</strong> — the professional healthcare network connecting medical practitioners, allied health professionals, and healthcare institutions across the globe.
            </p>

            <p>
                Before you can access your dashboard, please verify your email address by clicking the button below:
            </p>

            <div class="cta-wrap">
                <a href="{{ $verificationUrl }}" class="cta-btn">
                    Verify Email Address
                </a>
            </div>

            <p class="expiry-note">This link will expire in <strong>60 minutes</strong>.</p>

            <hr class="divider" />

            <p style="font-size:14px; color:#64748b; margin:0 0 8px;">
                If the button above doesn't work, copy and paste the link below into your browser:
            </p>
            <div class="url-box">{{ $verificationUrl }}</div>

            <div class="note">
                If you did not create an account on My Bridge International, no further action is required — you can safely ignore this email.
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>© {{ date('Y') }} My Bridge International. All rights reserved.</p>
            <p>
                Need help? <a href="mailto:mbi@connectinskillz.com">mbi@connectinskillz.com</a>
            </p>
        </div>

    </div>
</div>
</body>
</html>

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

        /* Patient header — teal/green tone */
        .header-patient {
            background: linear-gradient(135deg, #0891b2 0%, #0e7490 100%);
            padding: 36px 32px;
            text-align: center;
        }
        /* Doctor/professional header — blue tone */
        .header-doctor {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            padding: 36px 32px;
            text-align: center;
        }
        .header h1 {
            color: #ffffff;
            margin: 0 0 4px;
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -0.025em;
        }
        .header-subtitle {
            color: rgba(255,255,255,0.8);
            margin: 0;
            font-size: 13px;
            font-weight: 500;
        }

        .content { padding: 32px; }
        .greeting {
            font-size: 20px;
            font-weight: 700;
            color: #0f172a;
            margin-top: 0;
            margin-bottom: 16px;
        }
        .text {
            font-size: 15px;
            line-height: 1.65;
            color: #475569;
            margin-bottom: 20px;
        }

        /* Patient highlight box — teal */
        .highlight-box-patient {
            background-color: #f0fdfa;
            border-left: 4px solid #0891b2;
            padding: 16px;
            border-radius: 0 8px 8px 0;
            margin-bottom: 24px;
        }
        /* Doctor highlight box — blue */
        .highlight-box-doctor {
            background-color: #eff6ff;
            border-left: 4px solid #2563eb;
            padding: 16px;
            border-radius: 0 8px 8px 0;
            margin-bottom: 24px;
        }
        .highlight-box-patient p,
        .highlight-box-doctor p {
            margin: 0;
            font-size: 14px;
            line-height: 1.6;
            color: #334155;
        }

        .features-list {
            margin: 0 0 24px;
            padding: 0;
            list-style: none;
        }
        .features-list li {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 14px;
            color: #475569;
            padding: 6px 0;
        }
        .features-list li span.icon {
            font-size: 16px;
            line-height: 1.4;
            flex-shrink: 0;
        }

        .btn-container {
            text-align: center;
            margin: 28px 0 16px;
        }
        .btn-patient {
            display: inline-block;
            background-color: #0891b2;
            color: #ffffff !important;
            padding: 14px 32px;
            text-decoration: none;
            border-radius: 9999px;
            font-weight: 700;
            font-size: 15px;
            box-shadow: 0 4px 12px rgba(8,145,178,0.3);
        }
        .btn-doctor {
            display: inline-block;
            background-color: #2563eb;
            color: #ffffff !important;
            padding: 14px 32px;
            text-decoration: none;
            border-radius: 9999px;
            font-weight: 700;
            font-size: 15px;
            box-shadow: 0 4px 12px rgba(37,99,235,0.3);
        }

        .divider {
            border: none;
            border-top: 1px solid #f1f5f9;
            margin: 0 0 24px;
        }
        .footer {
            padding: 0 32px 32px;
            font-size: 14px;
            color: #64748b;
        }
        .footer-note {
            margin-top: 16px;
            font-size: 13px;
            line-height: 1.6;
            color: #94a3b8;
        }
    </style>
</head>
<body>
<table class="wrapper" width="100%">
    <tr>
        <td align="center">
            <table class="container" width="100%">

                @if(isset($user->user_type) && $user->user_type === 'patient')
                {{-- ========== PATIENT EMAIL ========== --}}

                <!-- Patient Header -->
                <tr>
                    <td class="header-patient">
                        <h1>My Bridge International</h1>
                        <p class="header-subtitle">Telemedicine &amp; Patient Portal</p>
                    </td>
                </tr>

                <!-- Patient Content -->
                <tr>
                    <td class="content">
                        <p class="greeting">Hello {{ $user->full_name ?? 'there' }} 👋</p>

                        <p class="text">
                            Welcome aboard! Your patient account on <strong>My Bridge International</strong> has been successfully created. We're excited to help you access world-class healthcare from the comfort of your home.
                        </p>

                        <div class="highlight-box-patient">
                            <p>
                                Your patient portal gives you direct access to certified medical professionals through secure video and chat consultations — anytime, anywhere.
                            </p>
                        </div>

                        <p class="text" style="font-weight: 600; color: #0f172a; margin-bottom: 12px;">Here's what you can do with your account:</p>
                        <ul class="features-list">
                            <li><span class="icon">💬</span> Start telemedicine sessions with a specialist doctor</li>
                            <li><span class="icon">📋</span> View your lab orders and diagnostic results</li>
                            <li><span class="icon">📅</span> Manage your appointments and consultation history</li>
                            <li><span class="icon">🔒</span> Securely communicate with your care team</li>
                        </ul>

                        <p class="text">
                            Click the button below to access your patient portal and get started on your healthcare journey.
                        </p>

                        <div class="btn-container">
                            <a href="https://telemedicine.mybridgeinternational.org" class="btn-patient">Access Patient Portal</a>
                        </div>
                    </td>
                </tr>

                @else
                {{-- ========== DOCTOR / PROFESSIONAL EMAIL ========== --}}

                <!-- Doctor Header -->
                <tr>
                    <td class="header-doctor">
                        <h1>My Bridge International</h1>
                        <p class="header-subtitle">Medical &amp; Professional Portal</p>
                    </td>
                </tr>

                <!-- Doctor Content -->
                <tr>
                    <td class="content">
                        <p class="greeting">Hello Dr. {{ $user->full_name ?? 'there' }} 👋</p>

                        <p class="text">
                            Your professional account on <strong>My Bridge International</strong> has been successfully created. Welcome to our global network of certified healthcare professionals.
                        </p>

                        <div class="highlight-box-doctor">
                            <p>
                                Your medical portal puts you in control of telemedicine consultations, lab order management, and real-time patient communication — all in one secure platform.
                            </p>
                        </div>

                        <p class="text" style="font-weight: 600; color: #0f172a; margin-bottom: 12px;">Your professional portal includes:</p>
                        <ul class="features-list">
                            <li><span class="icon">🩺</span> Conduct live telemedicine sessions (audio &amp; video)</li>
                            <li><span class="icon">🧪</span> Create and manage patient lab orders</li>
                            <li><span class="icon">📊</span> View and annotate diagnostic results</li>
                            <li><span class="icon">🌐</span> Connect with the global MBI healthcare community</li>
                            <li><span class="icon">📰</span> Publish and access medical journals &amp; research</li>
                        </ul>

                        <p class="text">
                            Click the button below to log in to your medical portal and complete your profile setup.
                        </p>

                        <div class="btn-container">
                            <a href="https://portal.mybridgeinternational.org" class="btn-doctor">Access Medical Portal</a>
                        </div>
                    </td>
                </tr>

                @endif

                <!-- Shared Footer -->
                <tr>
                    <td class="footer">
                        <hr class="divider" />
                        <p class="text" style="margin-bottom: 8px; color: #334155;">
                            Warm regards,<br />
                            <strong>The My Bridge International Team</strong>
                        </p>
                        <p class="footer-note">
                            If you did not register for this account or if you need any assistance, please reply to this email — we're always here to help. This is an automated message; please do not reply directly.
                        </p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>
</body>
</html>

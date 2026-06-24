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

        /* Doctor/professional header — rich blue tone */
        .header-doctor {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            padding: 40px 32px;
            text-align: center;
        }
        .header-doctor h1 {
            color: #ffffff;
            margin: 0 0 6px;
            font-size: 26px;
            font-weight: 800;
            letter-spacing: -0.025em;
        }
        .header-subtitle {
            color: rgba(255,255,255,0.9);
            margin: 0;
            font-size: 14px;
            font-weight: 500;
            letter-spacing: 0.05em;
            text-transform: uppercase;
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

        /* Status & highlight box — warm blue/amber alert for pending status */
        .status-box {
            background-color: #fef3c7;
            border-left: 4px solid #d97706;
            padding: 16px;
            border-radius: 0 8px 8px 0;
            margin-bottom: 24px;
        }
        .status-box p {
            margin: 0;
            font-size: 14px;
            line-height: 1.6;
            color: #78350f;
            font-weight: 500;
        }

        /* Credential verification table */
        .credential-table {
            width: 100%;
            margin-bottom: 24px;
            background-color: #f8fafc;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        .credential-table td {
            padding: 12px 16px;
            font-size: 14px;
            border-bottom: 1px solid #e2e8f0;
        }
        .credential-table tr:last-child td {
            border-bottom: none;
        }
        .credential-label {
            font-weight: 600;
            color: #475569;
            width: 35%;
        }
        .credential-value {
            color: #0f172a;
        }

        .features-list {
            margin: 0 0 24px;
            padding: 0;
            list-style: none;
        }
        .features-list li {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            font-size: 14px;
            color: #475569;
            padding: 8px 0;
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
            transition: all 0.2s ease;
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

                <!-- Medical Header -->
                <tr>
                    <td class="header-doctor">
                        <h1>My Bridge International</h1>
                        <p class="header-subtitle">Medical &amp; Professional Portal</p>
                    </td>
                </tr>

                <!-- Content -->
                <tr>
                    <td class="content">
                        <p class="greeting">Dear {{ $user->full_name ?? 'Practitioner' }} 👋</p>

                        <p class="text">
                            Thank you for registering your professional account on <strong>My Bridge International</strong>. We are proud to welcome dedicated healthcare practitioners to our global medical community.
                        </p>

                        <!-- Status Alert -->
                        <div class="status-box">
                            <p>
                                <strong>Verification Pending:</strong> To ensure patient safety and maintain compliance with medical regulations, our administration team is currently verifying the credentials you submitted.
                            </p>
                        </div>

                        <!-- Credentials Grid -->
                        <p class="text" style="font-weight: 600; color: #0f172a; margin-bottom: 12px;">Submitted Credentials for Review:</p>
                        <table class="credential-table" width="100%">
                            <tr>
                                <td class="credential-label">Role Category</td>
                                <td class="credential-value">{{ ucfirst($user->category ?? 'Medical Practitioner') }}</td>
                            </tr>
                            @if(!empty($user->specialisation))
                            <tr>
                                <td class="credential-label">Specialisation</td>
                                <td class="credential-value">{{ $user->specialisation }}</td>
                            </tr>
                            @endif
                            @if(!empty($user->institution))
                            <tr>
                                <td class="credential-label">Institution</td>
                                <td class="credential-value">{{ $user->institution }}</td>
                            </tr>
                            @endif
                            @if(!empty($user->license_number))
                            <tr>
                                <td class="credential-label">License Number</td>
                                <td class="credential-value">{{ $user->license_number }}</td>
                            </tr>
                            @endif
                        </table>

                        <p class="text">
                            Once our medical board approves your credentials, you will receive a confirmation email, and your account will be fully activated. 
                        </p>

                        <p class="text" style="font-weight: 600; color: #0f172a; margin-bottom: 12px;">Upon activation, you will be able to:</p>
                        <ul class="features-list">
                            <li><span class="icon">🤝</span> <strong>Connect with Peers:</strong> Build your network and collaborate with a global community of healthcare professionals.</li>
                            <li><span class="icon">🛍️</span> <strong>Access the Marketplace:</strong> Bid for donated medical products and equipment, and explore essential resources for your practice.</li>
                            <li><span class="icon">📅</span> <strong>Discover Medical Events:</strong> Stay informed and participate in upcoming clinical webinars, forums, and conferences.</li>
                            <li><span class="icon">🩺</span> <strong>Deliver Telemedicine:</strong> Conduct secure audio/video consultations and coordinate real-time care with patients.</li>
                        </ul>

                        <p class="text">
                            You can visit the medical portal to review your account details or track your application status.
                        </p>

                        <div class="btn-container">
                            <a href="https://portal.mybridgeinternational.org" class="btn-doctor">Access Medical Portal</a>
                        </div>
                    </td>
                </tr>

                <!-- Footer -->
                <tr>
                    <td class="footer">
                        <hr class="divider" />
                        <p class="text" style="margin-bottom: 8px; color: #334155;">
                            Warm regards,<br />
                            <strong>The My Bridge International Team</strong>
                        </p>
                        <p class="footer-note">
                            If you did not register for this account or if you believe this is an error, please contact our support team. This is an automated notification.
                        </p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>
</body>
</html>

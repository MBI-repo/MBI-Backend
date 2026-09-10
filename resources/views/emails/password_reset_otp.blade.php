<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">

        <meta
            name="viewport"
            content="width=device-width, initial-scale=1.0"
        >

        <meta
            name="color-scheme"
            content="light"
        >

        <meta
            name="supported-color-schemes"
            content="light"
        >

        <title>
            @if($type === 'otp')
                Password Reset OTP
            @elseif($type === 'verified')
                OTP Verified
            @elseif($type === 'password_changed')
                Password Changed
            @else
                Password Reset
            @endif
            — My Bridge International
        </title>

        <style>
            body {
                margin: 0;
                padding: 0;
                width: 100% !important;
                min-height: 100%;
                background-color: #f8fafc;
                font-family:
                    -apple-system,
                    BlinkMacSystemFont,
                    "Segoe UI",
                    Roboto,
                    Helvetica,
                    Arial,
                    sans-serif;
                color: #334155;
                -webkit-font-smoothing: antialiased;
            }

            * {
                box-sizing: border-box;
            }

            table {
                border-collapse: collapse;
            }

            img {
                border: 0;
                display: block;
                max-width: 100%;
            }

            .wrapper {
                width: 100%;
                background-color: #f8fafc;
                padding: 40px 16px;
            }

            .container {
                width: 100%;
                max-width: 600px;
                margin: 0 auto;
                background-color: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 16px;
                overflow: hidden;
            }

            /* =========================
            HEADER
            ========================== */

            .header {
                background-color: #1e40af;
                padding: 40px 32px;
                text-align: center;
            }

            .logo-box {
                display: inline-block;
                padding: 10px 18px;
                margin-bottom: 20px;
                background-color: rgba(255, 255, 255, 0.15);
                border-radius: 12px;
            }

            .logo-text {
                margin: 0;
                color: #ffffff;
                font-size: 20px;
                font-weight: 800;
                letter-spacing: 0.05em;
            }

            .icon {
                width: 72px;
                height: 72px;
                line-height: 72px;
                margin: 0 auto 20px;
                border-radius: 50%;
                background-color: rgba(255, 255, 255, 0.18);
                color: #ffffff;
                font-size: 32px;
                text-align: center;
            }

            .header h1 {
                margin: 0 0 8px;
                color: #ffffff;
                font-size: 25px;
                line-height: 1.3;
                font-weight: 800;
            }

            .header-subtitle {
                margin: 0;
                color: rgba(255, 255, 255, 0.9);
                font-size: 14px;
                line-height: 1.5;
            }

            /* =========================
            BODY
            ========================== */

            .body {
                padding: 36px 32px;
            }

            .greeting {
                margin: 0 0 16px;
                color: #1e293b;
                font-size: 20px;
                line-height: 1.4;
                font-weight: 700;
            }

            .body-text {
                margin: 0 0 16px;
                color: #475569;
                font-size: 15px;
                line-height: 1.7;
            }

            .body-text strong {
                color: #1e293b;
            }

            /* =========================
            OTP
            ========================== */

            .otp-box {
                margin: 28px 0;
                padding: 24px 18px;
                background-color: #eff6ff;
                border: 1px solid #bfdbfe;
                border-radius: 12px;
                text-align: center;
            }

            .otp-label {
                margin: 0 0 10px;
                color: #64748b;
                font-size: 12px;
                line-height: 1.4;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.08em;
            }

            .otp-code {
                margin: 0;
                color: #1e40af;
                font-size: 32px;
                line-height: 1.2;
                font-weight: 800;
                letter-spacing: 8px;
            }

            .expiry-note {
                margin: 0;
                color: #64748b;
                font-size: 13px;
                line-height: 1.5;
                text-align: center;
            }

            /* =========================
            SUCCESS
            ========================== */

            .success-box {
                margin: 24px 0;
                padding: 24px 20px;
                background-color: #f0fdf4;
                border: 1px solid #bbf7d0;
                border-radius: 12px;
                text-align: center;
            }

            .success-icon {
                margin: 0 0 10px;
                font-size: 36px;
                line-height: 1;
            }

            .success-title {
                margin: 0 0 8px;
                color: #166534;
                font-size: 17px;
                line-height: 1.4;
                font-weight: 700;
            }

            .success-text {
                margin: 0;
                color: #15803d;
                font-size: 14px;
                line-height: 1.6;
            }

            /* =========================
            SECURITY NOTE
            ========================== */

            .security-note {
                margin-top: 28px;
                padding: 15px 16px;
                background-color: #eff6ff;
                border-left: 4px solid #3b82f6;
                border-radius: 6px;
                color: #1e40af;
                font-size: 13px;
                line-height: 1.6;
            }

            /* =========================
            DIVIDER
            ========================== */

            .divider {
                height: 1px;
                margin: 28px 0;
                background-color: #e2e8f0;
                border: 0;
            }

            /* =========================
            FOOTER
            ========================== */

            .footer {
                padding: 24px 32px;
                background-color: #f8fafc;
                border-top: 1px solid #e2e8f0;
                text-align: center;
            }

            .footer p {
                margin: 0 0 6px;
                color: #94a3b8;
                font-size: 13px;
                line-height: 1.5;
            }

            .footer p:last-child {
                margin-bottom: 0;
            }

            /* =========================
            MOBILE
            ========================== */

            @media only screen and (max-width: 600px) {

                .wrapper {
                    padding: 20px 10px;
                }

                .container {
                    border-radius: 12px;
                }

                .header {
                    padding: 32px 20px;
                }

                .body {
                    padding: 28px 20px;
                }

                .footer {
                    padding: 22px 20px;
                }

                .header h1 {
                    font-size: 22px;
                }

                .otp-code {
                    font-size: 28px;
                    letter-spacing: 6px;
                }
            }
        </style>
    </head>

    <body>

        <div class="wrapper">

            <div class="container">

                {{-- ==========================================
                    HEADER
                =========================================== --}}

                <div class="header">

                    <div class="logo-box">
                        <p class="logo-text">MBI</p>
                    </div>

                    @if($type === 'otp')

                        <div class="icon">
                            🔐
                        </div>

                        <h1>
                            Password Reset OTP
                        </h1>

                        <p class="header-subtitle">
                            Your password reset verification code
                        </p>

                    @elseif($type === 'verified')

                        <div class="icon">
                            ✓
                        </div>

                        <h1>
                            OTP Verified Successfully
                        </h1>

                        <p class="header-subtitle">
                            Your password reset verification was successful
                        </p>

                    @elseif($type === 'password_changed')

                        <div class="icon">
                            🔒
                        </div>

                        <h1>
                            Password Changed Successfully
                        </h1>

                        <p class="header-subtitle">
                            Your account password has been updated
                        </p>

                    @else

                        <div class="icon">
                            ✉️
                        </div>

                        <h1>
                            Password Reset
                        </h1>

                        <p class="header-subtitle">
                            Account security notification
                        </p>

                    @endif

                </div>


                {{-- ==========================================
                    BODY
                =========================================== --}}

                <div class="body">

                    <p class="greeting">
                        Hi {{ $user->full_name }},
                    </p>


                    @if($type === 'otp')

                        <p class="body-text">
                            We received a request to reset the password
                            associated with your My Bridge International account.
                        </p>

                        <p class="body-text">
                            Use the verification code below to continue
                            resetting your password.
                        </p>


                        <div class="otp-box">

                            <p class="otp-label">
                                Your verification code
                            </p>

                            <p class="otp-code">
                                {{ $otp }}
                            </p>

                        </div>

                        <p class="expiry-note">
                            This OTP will expire in 10 minutes.
                        </p>


                        <div class="security-note">
                            <strong>Security notice:</strong>
                            If you did not request a password reset, please
                            ignore this email. Do not share this verification
                            code with anyone.
                        </div>


                    @elseif($type === 'verified')

                        <p class="body-text">
                            Your password reset verification code has been
                            successfully verified.
                        </p>


                        <div class="success-box">

                            <div class="success-icon">
                                ✓
                            </div>

                            <p class="success-title">
                                OTP Verified
                            </p>

                            <p class="success-text">
                                You can now create a new password for your
                                My Bridge International account.
                            </p>

                        </div>


                        <div class="security-note">
                            <strong>Security notice:</strong>
                            If you did not initiate this password reset,
                            please contact support immediately.
                        </div>


                    @elseif($type === 'password_changed')

                        <p class="body-text">
                            Your My Bridge International account password
                            has been successfully changed.
                        </p>


                        <div class="success-box">

                            <div class="success-icon">
                                ✓
                            </div>

                            <p class="success-title">
                                Password Changed
                            </p>

                            <p class="success-text">
                                Your new password is now active.
                                You can use it the next time you sign in.
                            </p>

                        </div>


                        <p class="body-text">
                            For your security, all existing sessions have
                            been signed out. You will need to log in again
                            using your new password.
                        </p>


                        <div class="security-note">
                            <strong>Security notice:</strong>
                            If you did not make this change, please contact
                            My Bridge International support immediately.
                        </div>


                    @else

                        <p class="body-text">
                            This is a security notification regarding your
                            My Bridge International account.
                        </p>

                    @endif


                    <hr class="divider">


                    <p class="body-text">
                        If you need assistance with your account, please
                        contact My Bridge International support.
                    </p>

                </div>


                {{-- ==========================================
                    FOOTER
                =========================================== --}}

                <div class="footer">

                    <p>
                        © {{ date('Y') }} My Bridge International.
                        All rights reserved.
                    </p>

                    <p>
                        This is an automated email. Please do not reply
                        directly to this message.
                    </p>

                </div>

            </div>

        </div>

    </body>
</html>

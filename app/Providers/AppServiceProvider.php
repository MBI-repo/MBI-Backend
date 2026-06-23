<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            $request = request();

            // Default fallback URLs
            $doctorFrontendUrl = env('DOCTOR_FRONTEND_URL', 'http://localhost:3002');
            $patientFrontendUrl = env('PATIENT_FRONTEND_URL', 'http://localhost:3000');

            $frontendUrl = $doctorFrontendUrl; // default fallback

            if ($request) {
                // If path points to patient API group
                if ($request->is('*patient/*')) {
                    $frontendUrl = $patientFrontendUrl;
                }

                // Check for dynamic headers
                $origin = $request->header('Origin') ?: $request->header('Referer');
                if ($origin) {
                    $parsed = parse_url($origin);
                    if (isset($parsed['scheme']) && isset($parsed['host'])) {
                        $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';
                        $frontendUrl = $parsed['scheme'] . '://' . $parsed['host'] . $port;
                    }
                }
            }

            return rtrim($frontendUrl, '/') . '/reset-password?token=' . $token . '&email=' . urlencode($notifiable->getEmailForPasswordReset());
        });
    }
}

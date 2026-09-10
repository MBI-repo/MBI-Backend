<?php
namespace App\Services;
use Illuminate\Support\Facades\Http;


class SendSmsService
{
    public function sendSms(string $phone, string $message)
    {
        $response = Http::post('https://api.smsapi.com/sms', [
            'api_key' => env('SMS_API_KEY'),
            'phone' => $phone,
            'message' => $message,
        ]);
        return $response->json();
    }
}
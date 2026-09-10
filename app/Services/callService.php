<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;

class CallService 
{
    public function sendCall(string $phone): array
    {
        $response = Http::post('https://api.smsapi.com/call', [
            'api_key' => env('SMS_API_KEY'),
            'phone' => $phone,
            
        ]);
        return $response->json();
    }
}


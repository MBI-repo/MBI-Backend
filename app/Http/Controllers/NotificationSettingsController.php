<?php

namespace App\Http\Controllers;

use App\Models\Notificationsettings;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;


class NotificationSettingsController extends Controller
{
    public function viewSettings()
    {
        try {
            $user = Auth::user();

            $setting = Notificationsettings::firstOrCreate(
                ['user_uuid' => $user->uuid],
                [
                    'notify_network' => true,
                    'notify_messages' => true,
                    'notify_events' => true,
                    'notify_system' => true,
                    'frequency' => 'instant'
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Notification settings retrieved.',
                'data' => $setting
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: '.$e->getMessage(),
            ], 500);
        }
    }
    public function updateSettings(Request $request)
    {
        try {
            $user = Auth::user();

            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authenticated user not found',
                ], 401);
            }

            // Validate input
            $validator = Validator::make($request->all(), [
                'network' => ['nullable', 'boolean'],
                'messages' => ['nullable', 'boolean'],
                'events' => ['nullable', 'boolean'],
                'system' => ['nullable', 'boolean'],

                'frequency' => ['required', 'array'],
                'frequency.instant' => ['required', 'boolean'],
                'frequency.daily' => ['required', 'boolean'],
                'frequency.weekly' => ['required', 'boolean'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Frequency must have exactly one true
            $freq = $request->frequency;
            $freqOptions = [
                'instant' => $freq['instant'],
                'daily' => $freq['daily'],
                'weekly' => $freq['weekly'],
            ];

            if (collect($freqOptions)->filter()->count() !== 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Exactly one frequency option must be selected.',
                ], 422);
            }

            $frequencyValue = array_search(true, $freqOptions);

            // Get or create settings record
            $setting = Notificationsettings::firstOrCreate(
                ['user_uuid' => $user->uuid]
            );

            if ($request->has('network')) {
                $setting->notify_network = $request->network;
            }
            if ($request->has('messages')) {
                $setting->notify_messages = $request->messages;
            }
            if ($request->has('events')) {
                $setting->notify_events = $request->events;
            }
            if ($request->has('system')) {
                $setting->notify_system = $request->system;
            }

            $setting->frequency = $frequencyValue;

            $setting->save();

            return response()->json([
                'success' => true,
                'message' => 'Notification settings updated successfully.',
                'data'    => $setting,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: '.$e->getMessage(),
            ], 500);
        }
    }

}

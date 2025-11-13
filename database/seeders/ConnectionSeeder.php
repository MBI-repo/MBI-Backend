<?php

namespace Database\Seeders;

use App\Models\Connection;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ConnectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Configuration: adjust as needed
        $targetAccepted = 30; // number of accepted connections to create
        $targetPending  = 25; // number of pending requests to create
        $targetRejected = 5;  // number of rejected requests to create

        $userIds = User::pluck('id')->toArray();
        $totalUsers = count($userIds);

        if ($totalUsers < 2) {
            $this->command->info('Not enough users to seed connections (need at least 2).');
            return;
        }

        // track created pairs to avoid duplicates (sender->receiver)
        $createdPairs = [];

        // helper to check if pair exists in memory or DB (exact pair or reverse)
        $pairExists = function ($sender, $receiver) use (&$createdPairs) {
            // exact pair already created in this run
            if (isset($createdPairs["{$sender}_{$receiver}"])) return true;
            // also check DB exact pair
            $exists = Connection::where('sender_id', $sender)
                                ->where('receiver_id', $receiver)
                                ->exists();
            if ($exists) return true;
            // avoid reverse pair (to keep single authoritative row)
            if (isset($createdPairs["{$receiver}_{$sender}"])) return true;
            $reverseExists = Connection::where('sender_id', $receiver)
                                       ->where('receiver_id', $sender)
                                       ->exists();
            if ($reverseExists) return true;

            return false;
        };

        // function to create a connection safely and record it
        $createConnection = function ($sender, $receiver, $status) use (&$createdPairs) {
            $row = Connection::create([
                'sender_id' => $sender,
                'receiver_id' => $receiver,
                'status' => $status,
            ]);
            $createdPairs["{$sender}_{$receiver}"] = true;
            return $row;
        };

        DB::beginTransaction();
        try {
            // Create accepted connections
            $attempts = 0;
            $created = 0;
            while ($created < $targetAccepted && $attempts < $targetAccepted * 20) {
                $attempts++;
                $pair = Arr::random($userIds, 2);
                $sender = $pair[0];
                $receiver = $pair[1];

                if ($sender === $receiver) continue;
                if ($pairExists($sender, $receiver)) continue;

                // create accepted
                $createConnection($sender, $receiver, 'accepted');
                $created++;
            }

            // Create pending requests
            $attempts = 0;
            $created = 0;
            while ($created < $targetPending && $attempts < $targetPending * 20) {
                $attempts++;
                $pair = Arr::random($userIds, 2);
                $sender = $pair[0];
                $receiver = $pair[1];

                if ($sender === $receiver) continue;
                if ($pairExists($sender, $receiver)) continue;

                $createConnection($sender, $receiver, 'pending');
                $created++;
            }

            // Create rejected requests
            $attempts = 0;
            $created = 0;
            while ($created < $targetRejected && $attempts < $targetRejected * 20) {
                $attempts++;
                $pair = Arr::random($userIds, 2);
                $sender = $pair[0];
                $receiver = $pair[1];

                if ($sender === $receiver) continue;
                if ($pairExists($sender, $receiver)) continue;

                $createConnection($sender, $receiver, 'rejected');
                $created++;
            }

            DB::commit();

            $this->command->info('Connection seeding completed: ' . count($createdPairs) . ' rows created.');
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->command->error('Connection seeder failed: ' . $e->getMessage());
            throw $e;
        }
    }
}

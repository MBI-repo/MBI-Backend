<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;


/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Notification>
 */
class NotificationFactory extends Factory
{
    protected $model = \App\Models\Notification::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
     public function definition()
    {
        $users = User::pluck('uuid'); // get existing UUIDs
        $receiver = $this->faker->randomElement($users);
        $sender = $this->faker->randomElement($users);


        return [
            'receiver_id'   => $receiver,
            'sender_id'     => $this->faker->boolean(80) ? $sender : null, // 20% system notifications
            'title'         => $this->faker->sentence(3),
            'message'       => $this->faker->paragraph(),
            'type'          => $this->faker->randomElement(['connection','comment','update','reminder']),
            'is_read'       => $this->faker->boolean(50),
            'reference_id'  => $this->faker->numberBetween(1, 100), // dummy reference
            'reference_type'=> $this->faker->randomElement(['post','profile','request']),
        ];
    }
}

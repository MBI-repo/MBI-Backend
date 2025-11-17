<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Load channel authorization definitions and register broadcasting routes
        require base_path('routes/channels.php');
    }
}
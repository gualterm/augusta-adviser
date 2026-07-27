<?php

namespace App\Providers;

use App\Models\Appointment;
use App\Models\Client;
use App\Observers\AppointmentObserver;
use App\Observers\ClientObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Client::observe(ClientObserver::class);
        Appointment::observe(AppointmentObserver::class);
    }
}

<?php
namespace App\Observers;

use App\Models\Client;
use App\Services\ActivityLogger;

class ClientObserver
{
    public function created(Client $client): void
    {
        $source    = 'system';
        $actorName = null;

        $path = request()->path();

        if (str_contains($path, 'consent') || str_contains($path, 'rgpd')) {
            $source = 'rgpd';
        } elseif (auth()->check() && auth()->user() instanceof \App\Models\User) {
            $source    = 'admin';
            $actorName = auth()->user()->name;
        } elseif (str_contains($path, 'portal')) {
            $source = 'portal';
        } elseif (str_contains($path, 'import') || app()->runningInConsole()) {
            $source = 'system'; // importações
            return; // não logar importações em massa
        }

        ActivityLogger::clientCreated($client, $source, $actorName);
    }
}
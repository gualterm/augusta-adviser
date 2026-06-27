<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    protected function redirectTo(Request $request): ?string
    {
        if (! $request->expectsJson()) {
            // Se o URL começa com /portal, redireciona para o login do portal
            if ($request->is('portal*')) {
                return route('portal.login');
            }
            return route('filament.admin.auth.login');
        }

        return null;
    }
}

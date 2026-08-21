<?php
namespace App\Filament\Auth\Responses;

use Filament\Auth\Http\Responses\Contracts\LoginResponse as Responsable;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class LoginResponse implements Responsable
{
    public function toResponse($request): RedirectResponse | Redirector
    {
        // Em PROD mostra o seletor de ambiente; em qualquer outro dominio
        // (DEV/formacao) ja nao ha nada para escolher, vai direto ao dashboard.
        $isProd = str_contains(config('app.url'), 'augustaadviser.pt');

        return redirect($isProd ? '/admin/ambiente' : '/admin');
    }
}

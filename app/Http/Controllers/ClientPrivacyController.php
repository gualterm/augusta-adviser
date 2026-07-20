<?php
namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class ClientPrivacyController extends Controller
{
    /** Página de preferências de privacidade do cliente */
    public function show(): View
    {
        $client  = auth('client')->user();
        $consent = $client->consents()->latest()->first();

        return view('portal.privacy', compact('client', 'consent'));
    }

    /** Actualizar preferência de marketing (toggle) */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'marketing_consent' => 'boolean',
        ]);

        auth('client')->user()->update([
            'marketing_consent' => $request->boolean('marketing_consent'),
        ]);

        return back()->with('privacy_success', 'Preferências de privacidade guardadas.');
    }

    /** Solicitar eliminação de dados (envia email à Augusta, não apaga automaticamente) */
    public function deleteRequest(Request $request): RedirectResponse
    {
        $client = auth('client')->user();

        $adminEmail = config('mail.admin_address', env('MAIL_ADMIN', 'noreply@augustaadviser.pt'));

        Mail::raw(
            "Pedido de eliminação de dados pessoais (RGPD — Art. 17.º)\n\n"
            . "Cliente: {$client->name}\n"
            . "Email: {$client->email}\n"
            . "ID: {$client->id}\n"
            . "Data do pedido: " . now()->format('d/m/Y H:i') . "\n\n"
            . "O cliente solicitou a eliminação permanente dos seus dados pessoais da plataforma Augusta.\n"
            . "Prazo legal de resposta: 30 dias.\n",
            function ($message) use ($client, $adminEmail) {
                $message->to($adminEmail)
                    ->replyTo($client->email, $client->name)
                    ->subject("Pedido RGPD — Eliminação de Dados: {$client->name}");
            }
        );

        return back()->with('delete_requested', true);
    }
}
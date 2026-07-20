<?php
namespace App\Http\Controllers;

use App\Mail\ConsentConfirmation;
use App\Models\Client;
use App\Models\ClientConsent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ConsentController extends Controller
{
    /**
     * Recebe a submissão do formulário de consentimento PWA.
     * Guarda o registo, actualiza o cliente se existir, e envia email de confirmação.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'              => 'required|string|max:255',
            'email'             => 'required|email|max:255',
            'phone'             => 'nullable|string|max:30',
            'birth_date'        => 'nullable|date',
            'marketing_consent' => 'boolean',
            'signature_data'    => 'nullable|string|max:500000', // base64 PNG
        ]);

        // Tentar associar a um cliente existente (pelo email)
        $client = Client::where('email', $data['email'])->first();

        $consent = ClientConsent::create([
            'client_id'         => $client?->id,
            'name'              => $data['name'],
            'email'             => $data['email'],
            'phone'             => $data['phone'] ?? null,
            'birth_date'        => $data['birth_date'] ?? null,
            'marketing_consent' => $data['marketing_consent'] ?? false,
            'ip_address'        => $request->ip(),
            'signature_data'    => $data['signature_data'] ?? null,
            'consented_at'      => now(),
        ]);

        // Marcar cliente como tendo dado consentimento
        if ($client) {
            $client->update(['consented_at' => now()]);
        }

        // Email de confirmação para o cliente
        try {
            Mail::to($data['email'])->send(new ConsentConfirmation($consent));
        } catch (\Throwable $e) {
            Log::error('ConsentConfirmation email falhou: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Consentimento registado com sucesso.',
            'ref'     => $consent->id,
        ]);
    }
}
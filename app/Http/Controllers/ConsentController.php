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
     * Recebe a submissão do formulário de consentimento.
     * Se o cliente já existir (por email), sobrepõe TODOS os campos fornecidos.
     * Guarda sempre um registo em client_consents para auditoria.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'              => 'required|string|max:255',
            'email'             => 'required|email|max:255',
            'phone'             => 'nullable|string|max:30',
            'birth_date'        => 'nullable|date',
            'nif'               => 'nullable|string|max:9',
            'morada'            => 'nullable|string|max:255',
            'codigo_postal'     => 'nullable|string|max:8',
            'localidade'        => 'nullable|string|max:100',
            'marketing_consent' => 'boolean',
            'signature_data'    => 'nullable|string|max:500000',
        ]);

        // Procurar cliente existente por email
        $client = Client::where('email', $data['email'])->first();

        // Campos a sobrepor no cliente (apenas os que foram preenchidos no formulário)
        $clientUpdate = array_filter([
            'name'              => $data['name'],
            'phone'             => $data['phone'] ?? null,
            'birth_date'        => $data['birth_date'] ?? null,
            'nif'               => $data['nif'] ?? null,
            'morada'            => $data['morada'] ?? null,
            'codigo_postal'     => $data['codigo_postal'] ?? null,
            'localidade'        => $data['localidade'] ?? null,
            'marketing_consent' => $data['marketing_consent'] ?? false,
            'consented_at'      => now(),
        ], fn ($v) => $v !== null);
        // marketing_consent pode ser false — forçar inclusão
        $clientUpdate['marketing_consent'] = $data['marketing_consent'] ?? false;
        $clientUpdate['consented_at']      = now();

        if ($client) {
            $client->update($clientUpdate);
        }

        // Guardar registo de auditoria em client_consents
        $consent = ClientConsent::create([
            'client_id'         => $client?->id,
            'name'              => $data['name'],
            'email'             => $data['email'],
            'phone'             => $data['phone'] ?? null,
            'birth_date'        => $data['birth_date'] ?? null,
            'nif'               => $data['nif'] ?? null,
            'morada'            => $data['morada'] ?? null,
            'codigo_postal'     => $data['codigo_postal'] ?? null,
            'localidade'        => $data['localidade'] ?? null,
            'marketing_consent' => $data['marketing_consent'] ?? false,
            'ip_address'        => $request->ip(),
            'signature_data'    => $data['signature_data'] ?? null,
            'consented_at'      => now(),
        ]);

        // Email de confirmação
        try {
            Mail::to($data['email'])->send(new ConsentConfirmation($consent));
        } catch (\Throwable $e) {
            Log::error('ConsentConfirmation email falhou: ' . $e->getMessage());
        }

        return response()->json([
            'success'        => true,
            'message'        => 'Consentimento registado com sucesso.',
            'ref'            => $consent->id,
            'client_updated' => $client !== null,
        ]);
    }
}
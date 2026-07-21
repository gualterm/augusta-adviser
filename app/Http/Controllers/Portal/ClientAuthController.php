<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ClientAuthController extends Controller
{
    public function showRegister()
    {
        return view('portal.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name'       => ['required', 'string', 'max:150'],
            'email'      => ['required', 'email', 'max:150', Rule::unique('clients', 'email')],
            'phone'      => ['required', 'string', 'max:30'],
            'gender'     => ['required', 'in:feminino,masculino'],
            'nif'        => ['required', 'string', 'max:20'],
            'birth_date' => ['nullable', 'date'],
            'morada'     => ['nullable', 'string', 'max:255'],
            'password'   => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'name.required'      => 'O nome é obrigatório.',
            'phone.required'     => 'O telemóvel é obrigatório.',
            'gender.required'    => 'O género é obrigatório.',
            'nif.required'       => 'O NIF é obrigatório.',
            'email.required'     => 'O email é obrigatório.',
            'email.unique'       => 'Este email já está registado.',
            'password.required'  => 'A password é obrigatória.',
            'password.min'       => 'A password deve ter pelo menos :min caracteres.',
            'password.confirmed' => 'As passwords não coincidem.',
        ]);

        $client = Client::create([
            'name'       => $data['name'],
            'email'      => $data['email'],
            'phone'      => $data['phone'],
            'gender'     => $data['gender'],
            'nif'        => $data['nif'],
            'birth_date' => $data['birth_date'] ?? null,
            'address'    => $data['morada'] ?? null,
            'morada'     => $data['morada'] ?? null,
            'password'   => $data['password'],
            'active'     => true,
        ]);

        Auth::guard('client')->login($client);
        $client->sendEmailVerificationNotification();
        return redirect()->route('portal.verification.notice');
    }

    public function showConsent()
    {
        $client = Auth::guard('client')->user();
        if ($client->data_consent_at) {
            return redirect()->route('portal.dashboard');
        }
        return view('portal.consent', compact('client'));
    }

    public function saveConsent(Request $request)
    {
        $request->validate([
            'signature_data' => ['required', 'string', 'min:10'],
        ], [
            'signature_data.required' => 'A assinatura é obrigatória.',
        ]);

        $client = Auth::guard('client')->user();

        $consent = \App\Models\ClientConsent::create([
            'client_id'         => $client->id,
            'name'              => $client->name,
            'email'             => $client->email,
            'phone'             => $client->phone,
            'birth_date'        => $client->birth_date,
            'nif'               => $client->nif,
            'morada'            => $client->address ?? $client->morada,
            'marketing_consent' => $request->boolean('marketing_consent'),
            'signature_data'    => $request->input('signature_data'),
            'ip_address'        => $request->ip(),
            'consented_at'      => now(),
        ]);
        $consent->refresh();

        $client->update([
            'data_consent_at'   => now(),
            'consented_at'      => now(),
            'marketing_consent' => $request->boolean('marketing_consent'),
        ]);

        try {
            \Illuminate\Support\Facades\Mail::to($client->email)
                ->send(new \App\Mail\ConsentConfirmation($consent));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('ConsentConfirmation portal: ' . $e->getMessage());
        }

        return redirect()->route('portal.dashboard')->with('consent_done', true);
    }

        public function showLogin()
    {
        return view('portal.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('client')->attempt($data, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'Credenciais inválidas.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        $client = Auth::guard('client')->user();
        if (!$client->hasVerifiedEmail()) { return redirect()->route('portal.verification.notice'); }
        if ($client->password_changed_at && $client->password_changed_at->diffInMonths(now()) >= 12) {
            session()->flash('suggest_password_change', true);
        }

        return redirect()->route('portal.dashboard');
    }

    public function logout(Request $request)
    {
        Auth::guard('client')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('portal.login');
    }

    public function showForgot()
    {
        return view('portal.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $client = \App\Models\Client::where('email', $request->email)->first();

        if ($client) {
            $token = Str::random(64);
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $request->email],
                ['token' => Hash::make($token), 'created_at' => now()]
            );
            $url = url('/portal/reset-password/' . $token . '?email=' . urlencode($request->email));
            Mail::send([], [], function($mail) use ($request, $url) {
                $mail->to($request->email)
                     ->subject('Recuperação de Password — Augusta Adviser')
                     ->html('<div style="font-family:sans-serif;max-width:500px;margin:auto;">
                        <h2 style="color:#6f5f54;">Augusta Adviser</h2>
                        <p>Recebemos um pedido de recuperação de password.</p>
                        <p><a href="' . $url . '" style="display:inline-block;padding:12px 24px;background:#7a6b5d;color:#fff;text-decoration:none;border-radius:30px;">Redefinir Password</a></p>
                        <p style="color:#9b8a7c;font-size:13px;">Este link expira em 60 minutos. Se não solicitaste, ignora este email.</p>
                     </div>');
            });
        }

        return back()->with('status', 'Se o email existir na nossa base de dados, receberás um link de recuperação em breve.');
    }

    public function showReset(Request $request, $token)
    {
        return view('portal.reset-password', ['token' => $token, 'email' => $request->email]);
    }

    public function processReset(Request $request)
    {
        $request->validate([
            'token'                 => 'required',
            'email'                 => 'required|email',
            'password'              => 'required|min:8|confirmed',
        ]);

        $record = DB::table('password_reset_tokens')->where('email', $request->email)->first();

        if (!$record || !Hash::check($request->token, $record->token)) {
            return back()->withErrors(['email' => 'Link inválido ou expirado.']);
        }

        if (now()->diffInMinutes($record->created_at) > 60) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return back()->withErrors(['email' => 'O link expirou. Por favor solicita um novo.']);
        }

        $client = \App\Models\Client::where('email', $request->email)->first();
        if (!$client) {
            return back()->withErrors(['email' => 'Email não encontrado.']);
        }

        // Bloquear reutilização se mudou há mais de 6 meses
        if ($client->password_changed_at && $client->password_changed_at->diffInMonths(now()) >= 6) {
            if (Hash::check($request->password, $client->password)) {
                return back()->withErrors(['password' => 'Por segurança, escolhe uma password diferente da anterior.'])->withInput();
            }
        }
        $client->update(['password' => $request->password, 'password_changed_at' => now()]);
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return redirect()->route('portal.login')->with('status', 'Password alterada com sucesso. Podes fazer login.');
    }


    /**
     * Cancela uma marcação do cliente (até 1 dia antes).
     */
    public function cancelAppointment(\Illuminate\Http\Request $request, int $id)
    {
        $client = \Illuminate\Support\Facades\Auth::guard('client')->user();

        $appointment = \App\Models\Appointment::where('id', $id)
            ->where('client_id', $client->id)
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->firstOrFail();

        // Só pode cancelar se faltar mais de 1 dia
        $appointmentDateTime = \Carbon\Carbon::parse(
            $appointment->appointment_date->format('Y-m-d') . ' ' . $appointment->appointment_time
        );

        if ($appointmentDateTime->diffInHours(now(), false) > -24) {
            return back()->withErrors(['cancel' => 'Só é possível cancelar até 24 horas antes da marcação. Para cancelamentos de última hora liga-nos diretamente.']);
        }

        $appointment->update(['status' => 'cancelled']);

        return redirect()->route('portal.dashboard')->with('cancel_success', true);
    }
}
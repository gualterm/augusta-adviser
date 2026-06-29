<?php
namespace App\Http\Controllers\Portal;
use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class ClientVerificationController extends Controller {
    public function notice() {
        $c = Auth::guard('client')->user();
        if ($c && $c->hasVerifiedEmail()) return redirect()->route('portal.dashboard');
        return view('portal.verify-email', ['email' => $c ? $c->email : '']);
    }
    public function verify(Request $request, int $id, string $hash) {
        $c = Client::findOrFail($id);
        if (!hash_equals($hash, sha1($c->getEmailForVerification()))) abort(403, 'Link invalido.');
        if (!$request->hasValidSignature()) return redirect()->route('portal.verification.notice')->withErrors(['verify' => 'O link expirou. Pede um novo abaixo.']);
        if (!$c->hasVerifiedEmail()) $c->markEmailAsVerified();
        Auth::guard('client')->login($c);
        return redirect()->route('portal.dashboard')->with('email_verified', true);
    }
    public function resend(Request $request) {
        $c = Auth::guard('client')->user();
        if ($c->hasVerifiedEmail()) return redirect()->route('portal.dashboard');
        $c->sendEmailVerificationNotification();
        return back()->with('resent', true);
    }
}
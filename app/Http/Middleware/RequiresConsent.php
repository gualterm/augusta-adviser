<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
class RequiresConsent
{
    public function handle(Request $request, Closure $next)
    {
        $client = auth('client')->user();
        if ($client && !$client->data_consent_at) {
            return redirect()->route('portal.consent');
        }
        return $next($request);
    }
}
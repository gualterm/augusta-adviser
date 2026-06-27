<?php

namespace App\Http\Controllers;

use App\Models\Inquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InquiryController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'subject' => ['required', 'in:marcacoes,informacoes_gerais,promocoes,outros'],
            'message' => ['required', 'string', 'max:3000'],
        ], [], [
            'name' => 'nome',
            'email' => 'email',
            'phone' => 'telefone',
            'subject' => 'assunto',
            'message' => 'mensagem',
        ]);

        Inquiry::create($validated);

        return back()
            ->with('inquiry_success', true)
            ->withFragment('inquerito');
    }
}

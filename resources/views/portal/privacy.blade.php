@extends('portal.layouts.app')
@section('content')
<div class="max-w-2xl mx-auto px-4 py-8">

    {{-- Título --}}
    <h1 class="text-2xl font-semibold text-gray-800 mb-1">Privacidade &amp; Consentimento</h1>
    <p class="text-sm text-gray-500 mb-6">Gere as suas preferências de dados pessoais e comunicações.</p>

    {{-- Mensagem de sucesso --}}
    @if(session('privacy_success'))
    <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 mb-6 text-sm">
        ✓ {{ session('privacy_success') }}
    </div>
    @endif

    @if(session('delete_requested'))
    <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-lg px-4 py-3 mb-6 text-sm">
        O seu pedido de eliminação de dados foi enviado. Receberá uma resposta em até 30 dias.
    </div>
    @endif

    {{-- Card: Estado do Consentimento RGPD --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 mb-5">
        <h2 class="font-semibold text-gray-700 mb-3">Consentimento de Dados (RGPD)</h2>

        @if($client->consented_at)
            <div class="flex items-center gap-3 mb-3">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    ✓ Consentimento obtido
                </span>
                <span class="text-sm text-gray-500">
                    em {{ $client->consented_at->format('d/m/Y') }}
                </span>
            </div>
            <p class="text-sm text-gray-600 leading-relaxed">
                Prestou o seu consentimento para o tratamento dos seus dados pessoais para fins de gestão de agendamentos
                e serviços de acordo com a nossa política de privacidade e o RGPD (Reg. EU 2016/679).
            </p>
        @else
            <div class="flex items-center gap-3 mb-3">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                    Não registado
                </span>
            </div>
            <p class="text-sm text-gray-600">
                Ainda não assinou o formulário de consentimento. Pode fazê-lo na recepção da Augusta.
            </p>
        @endif
    </div>

    {{-- Card: Marketing --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 mb-5">
        <h2 class="font-semibold text-gray-700 mb-1">Comunicações de Marketing</h2>
        <p class="text-sm text-gray-500 mb-4">
            Novidades, promoções e dicas de beleza da Augusta Beauty Adviser.
            Pode alterar esta preferência a qualquer momento.
        </p>

        <form method="POST" action="{{ route('portal.privacy.update') }}">
            @csrf
            @method('PATCH')
            <div class="flex items-center justify-between p-4 rounded-lg border border-gray-100 bg-gray-50">
                <div>
                    <p class="text-sm font-medium text-gray-700">Receber emails de marketing</p>
                    <p class="text-xs text-gray-500 mt-0.5">Promoções, novos serviços, dicas personalizadas</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer ml-4">
                    <input type="checkbox" name="marketing_consent" value="1"
                        {{ $client->marketing_consent ? 'checked' : '' }}
                        class="sr-only peer"
                        onchange="this.form.submit()">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:ring-2 peer-focus:ring-amber-300 rounded-full peer
                        peer-checked:after:translate-x-full peer-checked:after:border-white
                        after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                        after:bg-white after:border-gray-300 after:border after:rounded-full
                        after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-500"></div>
                </label>
            </div>
            {{-- hidden submit para browsers sem JS --}}
            <button type="submit" class="mt-2 text-xs text-gray-400 underline">Guardar preferência</button>
        </form>
    </div>

    {{-- Card: Direitos RGPD --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 mb-5">
        <h2 class="font-semibold text-gray-700 mb-3">Os seus Direitos (RGPD)</h2>
        <ul class="text-sm text-gray-600 space-y-2 mb-4">
            <li class="flex gap-2"><span class="text-amber-600 font-bold">→</span> <strong>Acesso:</strong> pode pedir uma cópia dos seus dados pessoais</li>
            <li class="flex gap-2"><span class="text-amber-600 font-bold">→</span> <strong>Rectificação:</strong> pode corrigir dados incorrectos no seu perfil</li>
            <li class="flex gap-2"><span class="text-amber-600 font-bold">→</span> <strong>Apagamento:</strong> pode solicitar a eliminação dos seus dados</li>
            <li class="flex gap-2"><span class="text-amber-600 font-bold">→</span> <strong>Portabilidade:</strong> pode exportar os seus dados em formato legível</li>
            <li class="flex gap-2"><span class="text-amber-600 font-bold">→</span> <strong>Reclamação:</strong> pode reclamar junto da <a href="https://www.cnpd.pt" target="_blank" class="text-amber-600 underline">CNPD</a></li>
        </ul>
        <p class="text-xs text-gray-400 mb-4">
            Para exercer os direitos de acesso ou portabilidade, contacte
            <a href="mailto:info@augustaadviser.pt" class="text-amber-600 underline">info@augustaadviser.pt</a>.
        </p>

        {{-- Solicitar eliminação --}}
        @if(!session('delete_requested'))
        <details class="mt-2">
            <summary class="text-sm text-red-600 cursor-pointer hover:text-red-800 select-none">
                ⚠ Solicitar eliminação dos meus dados pessoais
            </summary>
            <div class="mt-3 p-4 bg-red-50 border border-red-200 rounded-lg">
                <p class="text-sm text-red-800 mb-3">
                    Este pedido irá notificar a Augusta para eliminar os seus dados pessoais da plataforma.
                    Esta acção é <strong>irreversível</strong> e implica a perda de acesso ao portal e ao historial de marcações.
                </p>
                <form method="POST" action="{{ route('portal.privacy.delete-request') }}"
                    onsubmit="return confirm('Tem a certeza? Este pedido é irreversível.')">
                    @csrf
                    <button type="submit"
                        class="text-sm bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition">
                        Confirmar pedido de eliminação
                    </button>
                </form>
            </div>
        </details>
        @endif
    </div>

</div>
@endsection

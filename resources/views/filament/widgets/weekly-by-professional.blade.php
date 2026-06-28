<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            📈 Resumo Semanal por Profissional &nbsp;
            <span style="font-size:13px;font-weight:400;color:#6b7280;">{{ $weekStart }} – {{ $weekEnd }}</span>
        </x-slot>

        @if($data->isEmpty())
            <p style="color:#9ca3af;padding:16px 0;">Sem marcações esta semana.</p>
        @else
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px;padding:8px 0;">
            @php
                $colors = [
                    ['bg'=>'#eff6ff','border'=>'#3b82f6','icon'=>'💆','text'=>'#1d4ed8'],
                    ['bg'=>'#f0fdf4','border'=>'#22c55e','icon'=>'💅','text'=>'#15803d'],
                    ['bg'=>'#fdf4ff','border'=>'#a855f7','icon'=>'✨','text'=>'#7e22ce'],
                    ['bg'=>'#fff7ed','border'=>'#f97316','icon'=>'🤝','text'=>'#c2410c'],
                    ['bg'=>'#fef2f2','border'=>'#ef4444','icon'=>'⭐','text'=>'#b91c1c'],
                ];
                $i = 0;
            @endphp
            @foreach($data as $prof)
                @php $c = $colors[$i % count($colors)]; $i++; @endphp
                <div style="background:{{ $c['bg'] }};border:1px solid {{ $c['border'] }};border-radius:12px;padding:16px 20px;">
                    <div style="font-size:22px;margin-bottom:6px;">{{ $c['icon'] }}</div>
                    <div style="font-weight:600;color:{{ $c['text'] }};font-size:15px;margin-bottom:10px;">{{ $prof['name'] }}</div>
                    <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px;">
                        <span style="color:#6b7280;">Marcações</span>
                        <strong style="color:{{ $c['text'] }};">{{ $prof['count'] }}</strong>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px;">
                        <span style="color:#6b7280;">Confirmadas</span>
                        <strong style="color:{{ $c['text'] }};">{{ $prof['confirmed'] }}</strong>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:13px;border-top:1px solid {{ $c['border'] }};margin-top:8px;padding-top:8px;">
                        <span style="color:#6b7280;">Faturação</span>
                        <strong style="color:{{ $c['text'] }};">€ {{ number_format($prof['revenue'], 2, ',', '.') }}</strong>
                    </div>
                </div>
            @endforeach
        </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
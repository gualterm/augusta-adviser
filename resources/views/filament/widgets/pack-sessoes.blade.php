<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">🎫 Packs 6 Sessões — Controlo</x-slot>

        @php $clientes = $this->getData()['clientes']; @endphp

        @if(empty($clientes))
            <p class="text-sm text-gray-500">Nenhum pack activo de momento.</p>
        @else
        <table class="w-full text-sm" style="table-layout: fixed;">
            <colgroup>
                <col style="width: 30%">
                <col style="width: 10%">
                <col style="width: 13%">
                <col style="width: 13%">
                <col style="width: 13%">
                <col style="width: 21%">
            </colgroup>
            <thead>
                <tr class="border-b text-left text-gray-500">
                    <th class="py-2 pr-4">Cliente</th>
                    <th class="py-2 pr-4 text-center">Pack nº</th>
                    <th class="py-2 pr-4 text-center">Realizadas</th>
                    <th class="py-2 pr-4 text-center">Agendadas</th>
                    <th class="py-2 pr-4 text-center">Restantes</th>
                    <th class="py-2 text-right">Última / Próxima</th>
                </tr>
            </thead>
            <tbody>
                @foreach($clientes as $c)
                <tr class="border-b last:border-0 {{ $c['restantes'] <= 1 ? 'bg-amber-50' : '' }}">
                    <td class="py-2 pr-4 font-medium">{{ $c['cliente'] }}</td>
                    <td class="py-2 pr-4 text-center">{{ $c['pack'] }}</td>
                    <td class="py-2 pr-4 text-center">{{ $c['feitas'] }}</td>
                    <td class="py-2 pr-4 text-center">{{ $c['agendadas'] }}</td>
                    <td class="py-2 pr-4 text-center">
                        <span class="{{ $c['restantes'] <= 1 ? 'text-amber-600 font-bold' : 'text-green-600' }}">
                            {{ $c['restantes'] }}
                        </span>
                    </td>
                    <td class="py-2 text-right text-gray-500">
                        {{ $c['ultima'] ? \Carbon\Carbon::parse($c['ultima'])->format('d/m/Y') : '—' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <p class="mt-2 text-xs text-amber-600">⚠ Fundo amarelo = 1 ou menos sessões restantes no pack actual.</p>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>

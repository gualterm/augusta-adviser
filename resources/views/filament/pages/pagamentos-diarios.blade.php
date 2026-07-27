<x-filament-panels::page>
@php
    $data            = $this->getData();
    $grandTotal      = array_sum(array_column($data, 'total_value'));
    $grandCommission = array_sum(array_column($data, 'total_commission'));
    $formattedDate   = \Carbon\Carbon::parse($this->selectedDate ?: today())
                         ->translatedFormat('l, d \d\e F \d\e Y');
@endphp

<div class="space-y-6">
    {{-- Controlos --}}
    <div class="flex flex-wrap items-center justify-between gap-4 no-print">
        <div class="flex items-center gap-3">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Data:</label>
            <input
                type="date"
                wire:model.live="selectedDate"
                class="border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:outline-none"
            >
            <span class="text-sm text-gray-500 dark:text-gray-400">{{ $formattedDate }}</span>
        </div>
        <button
            onclick="window.print()"
            class="inline-flex items-center gap-2 bg-amber-700 hover:bg-amber-800 text-white text-sm font-medium px-4 py-2 rounded-lg transition"
        >
            🖨️ Imprimir / PDF
        </button>
    </div>

    {{-- Cards por profissional --}}
    @forelse ($data as $prof)
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        {{-- Cabeçalho do card --}}
        <div class="flex items-start justify-between px-6 py-4 bg-amber-50 dark:bg-amber-900/20 border-b border-amber-100 dark:border-amber-800">
            <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $prof['name'] }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ $prof['count'] }} {{ $prof['count'] === 1 ? 'marcação' : 'marcações' }}
                    &middot; confirmadas/concluídas
                </p>
            </div>
            <div class="text-right ml-4 flex-shrink-0">
                <div class="text-xs text-gray-400 mb-0.5">Total serviços</div>
                <div class="text-base font-semibold text-gray-700 dark:text-gray-200">
                    € {{ number_format($prof['total_value'], 2, ',', '.') }}
                </div>
                <div class="text-xs text-gray-400 mt-2 mb-0.5">A receber</div>
                <div class="text-2xl font-bold text-green-700 dark:text-green-400">
                    € {{ number_format($prof['total_commission'], 2, ',', '.') }}
                </div>
            </div>
        </div>

        {{-- Detalhe de serviços --}}
        <div class="px-6 py-4 overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs uppercase tracking-wide text-gray-400 border-b border-gray-100 dark:border-gray-700">
                        <th class="text-left pb-2 pr-4 font-medium">Hora</th>
                        <th class="text-left pb-2 pr-4 font-medium">Serviço</th>
                        <th class="text-left pb-2 pr-4 font-medium">Categoria</th>
                        <th class="text-right pb-2 pr-4 font-medium">Valor</th>
                        <th class="text-right pb-2 pr-4 font-medium">%</th>
                        <th class="text-right pb-2 font-medium">Comissão</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                    @foreach ($prof['breakdown'] as $row)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                        <td class="py-2 pr-4 font-mono text-gray-600 dark:text-gray-400">
                            {{ \Carbon\Carbon::parse($row['time'])->format('H:i') }}
                        </td>
                        <td class="py-2 pr-4 text-gray-900 dark:text-white">{{ $row['service'] }}</td>
                        <td class="py-2 pr-4 text-gray-500 dark:text-gray-400">{{ $row['category'] }}</td>
                        <td class="py-2 pr-4 text-right text-gray-700 dark:text-gray-300">
                            € {{ number_format($row['price'], 2, ',', '.') }}
                        </td>
                        <td class="py-2 pr-4 text-right">
                            <span class="inline-block bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-300 text-xs font-medium px-2 py-0.5 rounded-full">
                                {{ $row['pct'] }}%
                            </span>
                        </td>
                        <td class="py-2 text-right font-semibold text-green-700 dark:text-green-400">
                            € {{ number_format($row['commission'], 2, ',', '.') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-gray-200 dark:border-gray-600">
                        <td colspan="3" class="pt-3 font-semibold text-gray-600 dark:text-gray-400">
                            Total ({{ $prof['count'] }} marcações)
                        </td>
                        <td class="pt-3 text-right font-semibold text-gray-800 dark:text-gray-200">
                            € {{ number_format($prof['total_value'], 2, ',', '.') }}
                        </td>
                        <td></td>
                        <td class="pt-3 text-right font-bold text-base text-green-700 dark:text-green-400">
                            € {{ number_format($prof['total_commission'], 2, ',', '.') }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @empty
    <div class="text-center py-16 text-gray-400 dark:text-gray-500">
        <div class="text-5xl mb-4">💶</div>
        <p class="text-lg font-medium">Sem marcações confirmadas para {{ $formattedDate }}</p>
        <p class="text-sm mt-1">Escolhe uma data com marcações confirmadas ou concluídas.</p>
    </div>
    @endforelse

    {{-- Totais do dia --}}
    @if (!empty($data))
    <div class="bg-gray-900 dark:bg-gray-700 text-white rounded-xl px-6 py-5 no-print">
        <div class="flex flex-wrap justify-between items-center gap-4">
            <div>
                <div class="text-xs text-gray-400 uppercase tracking-wide mb-1">Resumo do dia</div>
                <div class="text-gray-300">
                    Total serviços:
                    <span class="font-semibold text-white ml-1">
                        € {{ number_format($grandTotal, 2, ',', '.') }}
                    </span>
                </div>
            </div>
            <div class="text-right">
                <div class="text-xs text-gray-400 uppercase tracking-wide mb-1">Total a pagar à equipa</div>
                <div class="text-3xl font-bold text-green-400">
                    € {{ number_format($grandCommission, 2, ',', '.') }}
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<style>
    @@media print {
        .no-print, nav, header, aside { display: none !important; }
        body { background: white !important; font-size: 11px; }
        .shadow-sm { box-shadow: none !important; }
        .rounded-xl { border-radius: 4px !important; }
    }
</style>
</x-filament-panels::page>
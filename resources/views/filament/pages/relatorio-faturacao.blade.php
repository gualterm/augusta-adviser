<x-filament-panels::page>
@php
    $data     = $this->getData();
    $titulo   = $this->getTitulo();
    $showDate = $this->tipo !== 'hoje';
    $meses    = $this->getMeses();
@endphp
<div class="space-y-4">
    <a href="/admin" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-amber-700 transition">
        ← Voltar ao Dashboard
    </a>

    {{-- Barra de filtros --}}
    <div class="flex flex-wrap items-center gap-3 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 px-4 py-3">
        @if($this->tipo === 'mes')
        <div class="flex items-center gap-2">
            <label class="text-xs font-medium text-gray-500 uppercase tracking-wide">Mês</label>
            <select wire:model.live="mes"
                class="text-sm border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-1.5 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                @foreach($meses as $valor => $label)
                    <option value="{{ $valor }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        @endif
        <div class="flex items-center gap-2">
            <label class="text-xs font-medium text-gray-500 uppercase tracking-wide">Origem</label>
            <select wire:model.live="origem"
                class="text-sm border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-1.5 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                <option value="todas">Todas</option>
                <option value="Odisseias">Odisseias</option>
                <option value="Direto">Direta</option>
            </select>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $titulo }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ $data['count'] }} {{ $data['count'] === 1 ? 'marcação' : 'marcações' }} confirmadas/concluídas
                </p>
            </div>
            <div class="text-right">
                <div class="text-xs text-gray-400 mb-1">Total faturado</div>
                <div class="text-2xl font-bold text-green-700 dark:text-green-400">
                    € {{ number_format($data['total'], 2, ',', '.') }}
                </div>
            </div>
        </div>
        @if ($data['appointments']->isEmpty())
        <div class="py-16 text-center text-gray-400">
            <div class="text-4xl mb-3">📭</div>
            <p class="text-base">Sem marcações confirmadas/concluídas para este período</p>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs uppercase tracking-wide text-gray-400 bg-gray-50 dark:bg-gray-800/50 border-b border-gray-100 dark:border-gray-700">
                        @if($showDate)
                        <th class="text-left px-4 py-3 font-medium">Data</th>
                        @endif
                        <th class="text-left px-4 py-3 font-medium">Hora</th>
                        <th class="text-left px-4 py-3 font-medium">Cliente</th>
                        <th class="text-left px-4 py-3 font-medium">Serviço</th>
                        <th class="text-left px-4 py-3 font-medium">Profissional</th>
                        <th class="text-right px-4 py-3 font-medium">Valor</th>
                        <th class="text-left px-4 py-3 font-medium">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                    @foreach ($data['appointments'] as $appt)
                    <tr class="hover:bg-amber-50/30 dark:hover:bg-gray-700/20 transition-colors">
                        @if($showDate)
                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs whitespace-nowrap">
                            {{ \Carbon\Carbon::parse($appt->appointment_date)->translatedFormat('d M') }}
                        </td>
                        @endif
                        <td class="px-4 py-3 font-mono text-gray-600 dark:text-gray-400 whitespace-nowrap">
                            {{ \Carbon\Carbon::parse($appt->appointment_time)->format('H:i') }}
                        </td>
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                            {{ $appt->client->name ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                            {{ $appt->service->name ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                            {{ $appt->employee->name ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-right font-semibold text-gray-800 dark:text-gray-200 whitespace-nowrap">
                            € {{ number_format((float) $appt->price, 2, ',', '.') }}
                        </td>
                        <td class="px-4 py-3">
                            @if($appt->status === 'confirmed')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                                ✓ Confirmada
                            </span>
                            @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300">
                                ✓ Concluída
                            </span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-gray-200 dark:border-gray-600 bg-gray-50/80 dark:bg-gray-800/80">
                        <td colspan="{{ $showDate ? 5 : 4 }}" class="px-4 py-4 font-semibold text-gray-600 dark:text-gray-300">
                            Total ({{ $data['count'] }} marcações)
                        </td>
                        <td class="px-4 py-4 text-right font-bold text-xl text-green-700 dark:text-green-400 whitespace-nowrap">
                            € {{ number_format($data['total'], 2, ',', '.') }}
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        @endif
    </div>
</div>
</x-filament-panels::page>

@php
    $highlight = 'ring-2 ring-danger-500 rounded-md px-1.5 py-0.5 -mx-1.5 inline-block';
    $statusLabel = fn (?string $status) => match ($status) {
        'scheduled' => 'Agendada',
        'confirmed' => 'Confirmada',
        'completed' => 'Concluída',
        'cancelled' => 'Cancelada',
        default => $status ?? '—',
    };
@endphp

<div class="space-y-4">
    <div class="rounded-lg border border-danger-300 bg-danger-50 px-4 py-3 text-sm text-danger-700 dark:border-danger-700 dark:bg-danger-950 dark:text-danger-300">
        ⚠ Este horário está a chocar
        @if($sameEmployee && $sameWorkstation)
            porque o profissional e o posto por omissão coincidem com uma marcação já existente
        @elseif($sameEmployee)
            porque o profissional por omissão coincide com uma marcação já existente
        @elseif($sameWorkstation)
            porque o posto por omissão coincide com uma marcação já existente
        @else
            com uma marcação já existente
        @endif
        @if($overlaps)
            , com horários sobrepostos.
        @else
            .
        @endif
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        {{-- Reserva externa --}}
        <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
            <p class="mb-3 text-sm font-semibold text-gray-500 dark:text-gray-400">
                Reserva Odisseias (por confirmar)
            </p>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between gap-2">
                    <dt class="text-gray-500 dark:text-gray-400">Cliente</dt>
                    <dd class="text-right font-medium">{{ $booking->client_name }}</dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt class="text-gray-500 dark:text-gray-400">Produto</dt>
                    <dd class="text-right font-medium">{{ $booking->product }}</dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt class="text-gray-500 dark:text-gray-400">Data</dt>
                    <dd class="text-right font-medium">{{ $bookingStart->format('d/m/Y') }}</dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt class="text-gray-500 dark:text-gray-400">Hora</dt>
                    <dd class="text-right font-medium">
                        <span class="{{ $overlaps ? $highlight : '' }}">{{ $bookingStart->format('H:i') }} – {{ $bookingEnd->format('H:i') }}</span>
                    </dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt class="text-gray-500 dark:text-gray-400">Profissional (por omissão)</dt>
                    <dd class="text-right font-medium">
                        <span class="{{ $sameEmployee ? $highlight : '' }}">{{ $defaultEmployee?->name ?? '—' }}</span>
                    </dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt class="text-gray-500 dark:text-gray-400">Posto (por omissão)</dt>
                    <dd class="text-right font-medium">
                        <span class="{{ $sameWorkstation ? $highlight : '' }}">{{ $defaultWorkstation?->name ?? '—' }}</span>
                    </dd>
                </div>
            </dl>
        </div>

        {{-- Marcação em conflito --}}
        <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
            <p class="mb-3 text-sm font-semibold text-gray-500 dark:text-gray-400">
                Marcação já existente @if($conflict) (#{{ $conflict->id }}) @endif
            </p>
            @if($conflict)
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-2">
                        <dt class="text-gray-500 dark:text-gray-400">Cliente</dt>
                        <dd class="text-right font-medium">{{ $conflict->client?->name ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-2">
                        <dt class="text-gray-500 dark:text-gray-400">Serviço</dt>
                        <dd class="text-right font-medium">{{ $conflict->service?->name ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-2">
                        <dt class="text-gray-500 dark:text-gray-400">Data</dt>
                        <dd class="text-right font-medium">{{ $conflictStart?->format('d/m/Y') ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-2">
                        <dt class="text-gray-500 dark:text-gray-400">Hora</dt>
                        <dd class="text-right font-medium">
                            <span class="{{ $overlaps ? $highlight : '' }}">{{ $conflictStart?->format('H:i') ?? '—' }} – {{ $conflictEnd?->format('H:i') ?? '—' }}</span>
                        </dd>
                    </div>
                    <div class="flex justify-between gap-2">
                        <dt class="text-gray-500 dark:text-gray-400">Profissional</dt>
                        <dd class="text-right font-medium">
                            <span class="{{ $sameEmployee ? $highlight : '' }}">{{ $conflict->employee?->name ?? '—' }}</span>
                        </dd>
                    </div>
                    <div class="flex justify-between gap-2">
                        <dt class="text-gray-500 dark:text-gray-400">Posto</dt>
                        <dd class="text-right font-medium">
                            <span class="{{ $sameWorkstation ? $highlight : '' }}">{{ $conflict->workstation?->name ?? '—' }}</span>
                        </dd>
                    </div>
                    <div class="flex justify-between gap-2">
                        <dt class="text-gray-500 dark:text-gray-400">Estado</dt>
                        <dd class="text-right font-medium">{{ $statusLabel($conflict->status) }}</dd>
                    </div>
                </dl>
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400">A marcação em conflito já não existe (pode ter sido apagada ou cancelada).</p>
            @endif
        </div>
    </div>
</div>

<x-filament-panels::page>
    <style>
        .ra-date-row{margin-bottom:20px;display:flex;align-items:center;gap:10px;}
        .ra-date-row label{font-weight:600;color:#6f5f54;}
        .ra-date-row input{padding:8px 12px;border:1px solid #d9d2c9;border-radius:8px;font-size:14px;}
        .ra-section-title{font-weight:700;color:#2f2a28;font-size:18px;margin:26px 0 12px;}
        .ra-grid{
            display:grid;
            grid-template-columns:repeat(5, 1fr);
            gap:14px;
        }
        @media (max-width:1400px){.ra-grid{grid-template-columns:repeat(4, 1fr);}}
        @media (max-width:1100px){.ra-grid{grid-template-columns:repeat(3, 1fr);}}
        @media (max-width:800px){.ra-grid{grid-template-columns:repeat(2, 1fr);}}
        @media (max-width:550px){.ra-grid{grid-template-columns:1fr;}}
        .ra-card{
            border:1px solid #e3ddd4;
            border-radius:14px;
            padding:14px;
            background:#fff;
        }
        .ra-card-equipment{
            border-left-width:4px;
        }
        .ra-card-title{font-weight:700;color:#2f2a28;font-size:15px;margin-bottom:2px;}
        .ra-card-type{font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#9b8a7c;margin-bottom:10px;display:block;}
        .ra-card-qty{font-size:11px;color:#9b8a7c;margin-bottom:10px;display:block;}
        .ra-free{
            background:#e8f3ea;
            color:#2f6b3f;
            border-radius:8px;
            padding:6px 8px;
            font-size:13px;
            font-weight:600;
            text-align:center;
            display:block;
            text-decoration:none;
            transition:.2s;
        }
        .ra-free:hover{background:#d9ecdd;}
        .ra-slot{
            border-radius:8px;
            padding:6px 8px;
            font-size:12.5px;
            margin-bottom:6px;
            line-height:1.3;
            display:block;
            text-decoration:none;
            transition:.2s;
        }
        .ra-slot-busy{
            background:#fdf1de;
            color:#8a5a1f;
            border:1px solid #f1d9ad;
        }
        .ra-slot-busy:hover{background:#fae6c4;}
        .ra-slot-overlap{
            background:#fbe4e4;
            color:#a13e3e;
            border:1px solid #f0bcbc;
        }
        .ra-slot-overlap:hover{background:#f7cfcf;}
        .ra-slot-time{font-weight:700;display:block;}
        .ra-legend{display:flex;gap:16px;margin-bottom:18px;flex-wrap:wrap;font-size:13px;color:#6f5f54;}
        .ra-legend span{display:inline-flex;align-items:center;gap:6px;}
        .ra-dot{width:10px;height:10px;border-radius:50%;display:inline-block;}
        .ra-add-link{
            display:block;
            text-align:center;
            margin-top:8px;
            font-size:12.5px;
            color:#7a6b5d;
            text-decoration:none;
            border:1px dashed #cdb9a9;
            border-radius:8px;
            padding:6px 8px;
        }
        .ra-add-link:hover{background:#f6f0eb;}
        .ra-empty-note{font-size:13px;color:#9b8a7c;}

        /* ── Timeline por profissional ── */
        .ra-tl-wrap{margin-bottom:24px;}
        .ra-tl-row{display:flex;align-items:stretch;gap:12px;margin-bottom:10px;min-height:64px;}
        .ra-tl-name{width:130px;font-weight:700;font-size:13px;display:flex;flex-direction:column;justify-content:center;flex-shrink:0;line-height:1.3;}
        .ra-tl-hours{font-size:10px;font-weight:400;color:#9b8a7c;margin-top:2px;}
        .ra-tl-bar{flex:1;display:flex;border-radius:10px;overflow:hidden;border:1px solid #e3ddd4;min-width:0;}
        .ra-tl-free{display:flex;flex-direction:column;justify-content:flex-start;padding:5px 6px;background:#e8f3ea;text-decoration:none;transition:.15s;min-width:0;overflow:hidden;cursor:pointer;}
        .ra-tl-free:hover{background:#c5e8cc;}
        .ra-tl-busy{display:flex;flex-direction:column;justify-content:flex-start;padding:5px 6px;text-decoration:none;transition:.15s;min-width:0;overflow:hidden;border-left:3px solid;}
        .ra-tl-busy:hover{filter:brightness(.93);}
        .ra-tl-time{font-size:10px;color:#5a4e45;font-weight:700;white-space:nowrap;}
        .ra-tl-label{font-size:10px;color:#2f6b3f;font-weight:600;}
        .ra-tl-client{font-size:12px;font-weight:700;color:#2f2a28;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
        .ra-tl-svc{font-size:10.5px;color:#6f5f54;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}

        /* ── Toggle Dia/Semana ── */
        .ra-toggle{display:flex;gap:0;border:1px solid #d9d2c9;border-radius:8px;overflow:hidden;width:fit-content;}
        .ra-toggle button{padding:7px 22px;font-size:13px;font-weight:600;border:none;cursor:pointer;background:#fff;color:#7a6b5d;transition:.15s;}
        .ra-toggle button.active{background:#c9a07a;color:#fff;}

        /* ── Vista Semanal ── */
        .ra-week-table{width:100%;border-collapse:collapse;font-size:12px;margin-bottom:24px;}
        .ra-week-table th{padding:8px 5px;text-align:center;font-weight:700;color:#6f5f54;border-bottom:2px solid #e3ddd4;font-size:10.5px;text-transform:uppercase;letter-spacing:.3px;}
        .ra-week-table td{padding:5px;vertical-align:top;border:1px solid #f0ebe5;min-width:90px;}
        .ra-week-emp{font-weight:700;font-size:12px;padding:8px 5px;white-space:nowrap;min-width:110px;}
        .ra-week-cell{min-height:58px;}
        .ra-week-closed{color:#c0b0a0;font-size:11px;text-align:center;padding-top:16px;font-style:italic;}
        .ra-week-free{display:block;text-align:center;color:#2f6b3f;font-size:11px;font-weight:600;margin-top:6px;text-decoration:none;}
        .ra-week-free:hover{text-decoration:underline;}
        .ra-week-appt{display:block;border-radius:4px;padding:3px 4px;margin-bottom:3px;font-size:11px;text-decoration:none;line-height:1.3;}
        .ra-week-appt:hover{filter:brightness(.92);}
        .ra-week-nav{display:flex;align-items:center;gap:12px;margin-bottom:14px;flex-wrap:wrap;}
        .ra-week-nav button{padding:5px 14px;border:1px solid #d9d2c9;border-radius:6px;background:#fff;cursor:pointer;font-size:13px;}
        .ra-week-nav button:hover{background:#f6f0eb;}
        .ra-week-label{font-weight:700;color:#2f2a28;font-size:14px;}
    </style>

    <div style="display:flex;align-items:center;gap:16px;margin-bottom:20px;flex-wrap:wrap;">
        <div class="ra-toggle">
            <button wire:click="$set('viewMode', 'day')"
                    class="{{ $viewMode === 'day' ? 'active' : '' }}">Dia</button>
            <button wire:click="$set('viewMode', 'week')"
                    class="{{ $viewMode === 'week' ? 'active' : '' }}">Semana</button>
        </div>
        @if($viewMode === 'day')
        <div class="ra-date-row" style="margin:0;">
            <label for="ra-date">Data</label>
            <input id="ra-date" type="date" wire:model.live="date" />
        </div>
        @endif
    </div>

    @if($viewMode === 'day')
    <div class="ra-legend">
        <span><span class="ra-dot" style="background:#2f6b3f;"></span> Livre (clicar para marcar)</span>
        <span><span class="ra-dot" style="background:#8a5a1f;"></span> Ocupado (clicar para editar)</span>
        <span><span class="ra-dot" style="background:#a13e3e;"></span> Sobreposto / capacidade esgotada</span>
    </div>

    <div class="ra-section-title">Salas e Postos</div>
    <div class="ra-grid">
        @foreach ($this->getWorkstationsWithAppointments() as $workstation)
            <div class="ra-card">
                <div class="ra-card-title">{{ $workstation->name }}</div>
                <span class="ra-card-type">{{ $workstation->type }}</span>

                @if ($workstation->dayAppointments->isEmpty())
                    <a href="{{ $workstation->createUrl }}" class="ra-free">Livre todo o dia</a>
                @else
                    @foreach ($workstation->dayAppointments as $appointment)
                        <a
                            href="{{ $appointment->editUrl }}"
                            class="ra-slot {{ $appointment->isOverlapping ? 'ra-slot-overlap' : 'ra-slot-busy' }}"
                        >
                            <span class="ra-slot-time">
                                {{ \Illuminate\Support\Carbon::parse($appointment->appointment_time)->format('H:i') }}–{{ \Illuminate\Support\Carbon::parse($appointment->end_time)->format('H:i') }}
                            </span>
                            {{ $appointment->employee?->name }} · {{ $appointment->client?->name }}
                        </a>
                    @endforeach
                    <a href="{{ $workstation->createUrl }}" class="ra-add-link">+ Adicionar marcação</a>
                @endif
            </div>
        @endforeach
    </div>

    @php $equipmentList = $this->getEquipmentWithAppointments(); @endphp

    @if ($equipmentList->isNotEmpty())
        <div class="ra-section-title">Equipamentos Partilhados</div>
        <div class="ra-grid">
            @foreach ($equipmentList as $equipment)
                <div class="ra-card ra-card-equipment" style="border-left-color: {{ $equipment->color }};">
                    <div class="ra-card-title" style="color: {{ $equipment->color }};">{{ $equipment->name }}</div>
                    <span class="ra-card-qty">{{ $equipment->quantity }} {{ $equipment->quantity == 1 ? 'unidade' : 'unidades' }}</span>

                    @if ($equipment->dayAppointments->isEmpty())
                        <div class="ra-free">Livre todo o dia</div>
                    @else
                        @foreach ($equipment->dayAppointments as $appointment)
                            <a
                                href="{{ $appointment->editUrl }}"
                                class="ra-slot {{ $appointment->isOverlapping ? 'ra-slot-overlap' : 'ra-slot-busy' }}"
                            >
                                <span class="ra-slot-time">
                                    {{ \Illuminate\Support\Carbon::parse($appointment->appointment_time)->format('H:i') }}–{{ \Illuminate\Support\Carbon::parse($appointment->end_time)->format('H:i') }}
                                </span>
                                {{ $appointment->workstation?->name }} · {{ $appointment->employee?->name }} · {{ $appointment->client?->name }}
                            </a>
                        @endforeach
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <p class="ra-empty-note">Ainda não há equipamentos registados. Cria-os em "Equipamentos" no menu.</p>
    @endif

    <div class="ra-section-title">Agenda por Profissional</div>

    @php $timelines = $this->getEmployeeTimeline(); @endphp

    @if ($timelines->isEmpty())
        <p class="ra-empty-note">Loja encerrada neste dia.</p>
    @else
        <div class="ra-tl-wrap">
            @foreach ($timelines as $emp)
                <div class="ra-tl-row">
                    <div class="ra-tl-name" style="color: {{ $emp['color'] }}">
                        {{ $emp['name'] }}
                        <span class="ra-tl-hours">{{ $emp['dayStart'] }}–{{ $emp['dayEnd'] }}</span>
                    </div>
                    <div class="ra-tl-bar">
                        @foreach ($emp['blocks'] as $block)
                            @if ($block['type'] === 'free')
                                <a
                                    href="{{ $block['createUrl'] }}"
                                    class="ra-tl-free"
                                    style="width: {{ $block['pct'] }}%"
                                    title="Livre {{ $block['start'] }}–{{ $block['end'] }} · clique para marcar"
                                >
                                    <span class="ra-tl-time">{{ $block['start'] }}</span>
                                    <span class="ra-tl-label">livre</span>
                                </a>
                            @else
                                <a
                                    href="{{ $block['editUrl'] }}"
                                    class="ra-tl-busy"
                                    style="width: {{ $block['pct'] }}%; border-left-color: {{ $emp['color'] }}; background: {{ $emp['color'] }}22;"
                                    title="{{ $block['start'] }}–{{ $block['end'] }}: {{ $block['client'] }} · {{ $block['service'] }}"
                                >
                                    <span class="ra-tl-time">{{ $block['start'] }}</span>
                                    <span class="ra-tl-client">{{ $block['client'] }}</span>
                                    <span class="ra-tl-svc">{{ Str::limit($block['service'], 20) }}</span>
                                </a>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif

@endif

@if($viewMode === 'week')
    @php $weekly = $this->getWeeklyTimeline(); @endphp
    <div class="ra-week-nav">
        <button wire:click="previousWeek">← Semana anterior</button>
        <span class="ra-week-label">{{ $weekly['weekStartLabel'] }}</span>
        <button wire:click="nextWeek">Semana seguinte →</button>
        <button wire:click="thisWeek" style="color:#9b8a7c;font-size:12px;margin-left:6px;">hoje</button>
    </div>

    <div style="overflow-x:auto;">
        <table class="ra-week-table">
            <thead>
                <tr>
                    <th style="text-align:left;min-width:110px;">Profissional</th>
                    @foreach($weekly['days'] as $dayLabel)
                        <th>{{ $dayLabel }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($weekly['employees'] as $emp)
                    <tr>
                        <td class="ra-week-emp"
                            style="color:{{ $emp['color'] }};border-left:3px solid {{ $emp['color'] }};">
                            {{ $emp['name'] }}
                        </td>
                        @foreach($emp['weekData'] as $day)
                            <td class="ra-week-cell">
                                @if(!$day['isOpen'])
                                    <div class="ra-week-closed">fechado</div>
                                @elseif(empty($day['appointments']))
                                    <a href="{{ $day['createUrl'] }}" class="ra-week-free">
                                        livre<br>+ marcar
                                    </a>
                                @else
                                    @foreach($day['appointments'] as $appt)
                                        <a href="{{ $appt['editUrl'] }}" class="ra-week-appt"
                                           style="background:{{ $emp['color'] }}22;border-left:2px solid {{ $emp['color'] }};"
                                           title="{{ $appt['time'] }}–{{ $appt['end'] }}: {{ $appt['client'] }} · {{ $appt['service'] }}">
                                            <strong>{{ $appt['time'] }}</strong>
                                            {{ Str::limit($appt['client'], 11) }}
                                        </a>
                                    @endforeach
                                    <a href="{{ $day['createUrl'] }}" class="ra-week-free"
                                       style="font-size:10px;margin-top:3px;">+ marcar</a>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
</x-filament-panels::page>

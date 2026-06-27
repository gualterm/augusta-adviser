<x-filament-panels::page>
    <style>
        .wc-nav{
            display:flex;
            align-items:center;
            gap:14px;
            margin-bottom:20px;
            flex-wrap:wrap;
        }
        .wc-nav button{
            padding:8px 16px;
            border-radius:8px;
            border:1px solid #d9d2c9;
            background:#fff;
            color:#6f5f54;
            font-size:13px;
            font-weight:600;
            cursor:pointer;
        }
        .wc-nav button:hover{background:#f6f0eb;}
        .wc-range{font-weight:700;color:#2f2a28;font-size:15px;}
        .wc-grid{
            display:grid;
            grid-template-columns:repeat(7, 1fr);
            gap:14px;
        }
        @media (max-width:1300px){.wc-grid{grid-template-columns:repeat(4, 1fr);}}
        @media (max-width:900px){.wc-grid{grid-template-columns:repeat(2, 1fr);}}
        @media (max-width:550px){.wc-grid{grid-template-columns:1fr;}}
        .wc-day{
            border:1px solid #e3ddd4;
            border-radius:14px;
            padding:12px;
            background:#fff;
            min-height:120px;
        }
        .wc-day-today{
            border-width:2px;
            box-shadow:0 0 0 2px rgba(0,0,0,.03);
        }
        .wc-day-title{
            font-weight:700;
            font-size:13.5px;
            margin-bottom:10px;
            padding-bottom:8px;
            border-bottom:2px solid;
        }
        .wc-free{
            background:#e8f3ea;
            color:#2f6b3f;
            border-radius:8px;
            padding:6px 8px;
            font-size:12.5px;
            font-weight:600;
            text-align:center;
            display:block;
            text-decoration:none;
        }
        .wc-free:hover{background:#d9ecdd;}
        .wc-slot{
            display:block;
            text-decoration:none;
            border-radius:8px;
            padding:6px 8px;
            font-size:12px;
            margin-bottom:6px;
            line-height:1.3;
            background:#fdf1de;
            color:#8a5a1f;
            border:1px solid #f1d9ad;
        }
        .wc-slot:hover{background:#fae6c4;}
        .wc-slot-time{font-weight:700;display:block;}
        .wc-add-link{
            display:block;
            text-align:center;
            margin-top:6px;
            font-size:11.5px;
            color:#7a6b5d;
            text-decoration:none;
            border:1px dashed #cdb9a9;
            border-radius:8px;
            padding:5px 6px;
        }
        .wc-add-link:hover{background:#f6f0eb;}
    </style>

    <div class="wc-nav">
        <button type="button" wire:click="previousWeek">&larr; Semana anterior</button>
        <button type="button" wire:click="thisWeek">Esta semana</button>
        <button type="button" wire:click="nextWeek">Semana seguinte &rarr;</button>
        <span class="wc-range">{{ $this->getWeekRangeLabel() }}</span>
    </div>

    <div class="wc-grid">
        @foreach ($this->getDaysWithAppointments() as $day)
            <div class="wc-day {{ $day->isToday ? 'wc-day-today' : '' }}">
                <div class="wc-day-title" style="color: {{ $day->color }}; border-bottom-color: {{ $day->color }};">
                    {{ $day->label }}
                </div>

                @if ($day->appointments->isEmpty())
                    <a href="{{ $day->createUrl }}" class="wc-free">Sem marcações</a>
                @else
                    @foreach ($day->appointments as $appointment)
                        <a href="{{ $appointment->editUrl }}" class="wc-slot">
                            <span class="wc-slot-time">
                                {{ \Illuminate\Support\Carbon::parse($appointment->appointment_time)->format('H:i') }}–{{ \Illuminate\Support\Carbon::parse($appointment->end_time)->format('H:i') }}
                            </span>
                            {{ $appointment->employee?->name }} · {{ $appointment->client?->name }}<br>
                            <span style="opacity:.8;">{{ $appointment->workstation?->name }}</span>
                        </a>
                    @endforeach
                    <a href="{{ $day->createUrl }}" class="wc-add-link">+ Adicionar</a>
                @endif
            </div>
        @endforeach
    </div>
</x-filament-panels::page>

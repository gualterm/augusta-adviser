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
    </style>

    <div class="ra-date-row">
        <label for="ra-date">Data</label>
        <input id="ra-date" type="date" wire:model.live="date" />
    </div>

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

    <div class="ra-section-title">Profissionais</div>
    <div class="ra-grid">
        @foreach ($this->getEmployeesWithAppointments() as $employee)
            <div class="ra-card ra-card-equipment" style="border-left-color: {{ $employee->color }};">
                <div class="ra-card-title" style="color: {{ $employee->color }};">{{ $employee->name }}</div>
                <span class="ra-card-type">{{ $employee->role ?? '' }}</span>

                @if ($employee->dayAppointments->isEmpty())
                    <a href="{{ $employee->createUrl }}" class="ra-free">Livre todo o dia</a>
                @else
                    @foreach ($employee->dayAppointments as $appointment)
                        <a
                            href="{{ $appointment->editUrl }}"
                            class="ra-slot {{ $appointment->isOverlapping ? 'ra-slot-overlap' : 'ra-slot-busy' }}"
                        >
                            <span class="ra-slot-time">
                                {{ \Illuminate\Support\Carbon::parse($appointment->appointment_time)->format('H:i') }}–{{ \Illuminate\Support\Carbon::parse($appointment->end_time)->format('H:i') }}
                            </span>
                            {{ $appointment->workstation?->name }} · {{ $appointment->client?->name }}
                        </a>
                    @endforeach
                    <a href="{{ $employee->createUrl }}" class="ra-add-link">+ Adicionar marcação</a>
                @endif
            </div>
        @endforeach
    </div>
</x-filament-panels::page>

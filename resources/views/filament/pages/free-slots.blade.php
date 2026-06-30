<x-filament-panels::page>
<style>
    .fs-nav{display:flex;align-items:center;gap:12px;margin-bottom:20px;flex-wrap:wrap;}
    .fs-nav button{padding:6px 16px;border:1px solid #d9d2c9;border-radius:7px;background:#fff;cursor:pointer;font-size:13px;font-weight:500;}
    .fs-nav button:hover{background:#f6f0eb;}
    .fs-week-label{font-weight:700;font-size:15px;color:#2f2a28;min-width:180px;text-align:center;}
    .fs-hint{font-size:12.5px;color:#9b8a7c;margin-left:auto;}

    .fs-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:18px;margin-bottom:32px;}
    .fs-day-card{background:#fff;border:1px solid #e8e2db;border-radius:12px;overflow:hidden;}
    .fs-day-header{padding:10px 16px;font-weight:700;font-size:13.5px;color:#fff;display:flex;justify-content:space-between;align-items:center;}
    .fs-day-today   .fs-day-header{background:#c9a07a;}
    .fs-day-tomorrow .fs-day-header{background:#8a9e7a;}
    .fs-day-other   .fs-day-header{background:#7a8a9e;}
    .fs-slot-count{font-size:11px;font-weight:400;opacity:.85;}

    .fs-slots{padding:10px 12px;display:flex;flex-direction:column;gap:6px;}
    .fs-slot{display:flex;align-items:center;gap:10px;text-decoration:none;padding:7px 10px;border-radius:7px;border:1px solid #ede8e2;transition:.15s;background:#faf8f6;}
    .fs-slot:hover{background:#f0ebe3;border-color:#c9a07a;}
    .fs-slot-time{font-weight:700;font-size:14px;color:#2f2a28;min-width:42px;}
    .fs-slot-emps{font-size:12px;color:#6f5f54;flex:1;line-height:1.3;}
    .fs-slot-badge{font-size:11px;font-weight:600;background:#e8f3ea;color:#2f6b3f;border-radius:20px;padding:2px 8px;white-space:nowrap;}
    .fs-empty{padding:20px;text-align:center;color:#b0a090;font-size:13px;font-style:italic;}
    .fs-no-slots{padding:32px;text-align:center;color:#b0a090;}
</style>

@php
    $days = $this->getFreeSlots();
    $periodStart = \Carbon\Carbon::today()->addWeeks($dayOffset)->format('d/m');
    $periodEnd   = \Carbon\Carbon::today()->addWeeks($dayOffset)->addDays($daysAhead - 1)->format('d/m');
@endphp

<div class="fs-nav">
    <button wire:click="$set('dayOffset', {{ $dayOffset - 1 }})">← Semana anterior</button>
    <span class="fs-week-label">{{ $periodStart }} – {{ $periodEnd }}</span>
    <button wire:click="$set('dayOffset', {{ $dayOffset + 1 }})">Semana seguinte →</button>
    <button wire:click="$set('dayOffset', 0)" style="color:#9b8a7c;">hoje</button>
    <span class="fs-hint">Clica num horário para abrir o formulário de marcação</span>
</div>

@if(empty($days))
    <div class="fs-no-slots">
        <x-heroicon-o-calendar-x-mark style="width:48px;height:48px;color:#d9d2c9;margin:0 auto 12px;" />
        <p>Sem horários livres para este período.</p>
    </div>
@else
    <div class="fs-grid">
        @foreach($days as $day)
            @php
                $cardClass = $day['isToday'] ? 'fs-day-today' : ($day['isTomorrow'] ? 'fs-day-tomorrow' : 'fs-day-other');
            @endphp
            <div class="fs-day-card {{ $cardClass }}">
                <div class="fs-day-header">
                    <span>
                        @if($day['isToday']) Hoje · @elseif($day['isTomorrow']) Amanhã · @endif
                        {{ $day['shortDay'] }}
                    </span>
                    <span class="fs-slot-count">{{ $day['total'] }} slot{{ $day['total'] != 1 ? 's' : '' }}</span>
                </div>
                <div class="fs-slots">
                    @foreach($day['slots'] as $slot)
                        <a href="{{ $slot['createUrl'] }}" class="fs-slot">
                            <span class="fs-slot-time">{{ $slot['time'] }}</span>
                            <span class="fs-slot-emps">{{ implode(', ', $slot['freeEmployees']) }}</span>
                            <span class="fs-slot-badge">{{ $slot['total'] }} livre{{ $slot['total'] != 1 ? 's' : '' }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
@endif
</x-filament-panels::page>
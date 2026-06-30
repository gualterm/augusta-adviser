<?php

namespace App\Filament\Widgets;

use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Filament\Widgets\ChartWidget;

class RevenueByWeekChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    public function getHeading(): string|\Illuminate\Contracts\Support\Htmlable|null
    {
        return '💶 Faturação por Semana (últimas 12 semanas)';
    }

    public static function getSort(): int { return 10; }

    protected function getType(): string { return 'bar'; }

    protected function getData(): array
    {
        $weeks = collect(range(11, 0))->map(fn ($i) => [
            'start' => Carbon::now()->startOfWeek()->subWeeks($i),
            'end'   => Carbon::now()->startOfWeek()->subWeeks($i)->endOfWeek(),
        ]);

        $labels  = $weeks->map(fn ($w) => $w['start']->format('d/m'))->toArray();
        $revenue = $weeks->map(fn ($w) =>
            (float) Appointment::whereBetween('appointment_date', [$w['start']->toDateString(), $w['end']->toDateString()])
                ->whereIn('status', ['confirmed', 'completed'])
                ->sum('price')
        )->toArray();

        return [
            'labels'   => $labels,
            'datasets' => [[
                'label'           => 'Faturação (€)',
                'data'            => $revenue,
                'backgroundColor' => array_map(
                    fn ($v) => $v > 0 ? '#5c4a3a' : '#d1c5bb',
                    $revenue
                ),
                'borderColor'     => '#3d2f25',
                'borderWidth'     => 1,
                'borderRadius'    => 6,
            ]],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['display' => false]],
            'scales'  => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks'       => ['callback' => "function(v){ return '€'+v; }"],
                ],
            ],
        ];
    }
}
<?php

namespace App\Filament\Widgets;

use App\Models\Appointment;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class RevenueByMonthChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    public function getHeading(): string|\Illuminate\Contracts\Support\Htmlable|null
    {
        return '📊 Faturação por Mês (últimos 12 meses)';
    }

    public static function getSort(): int { return 11; }

    protected function getType(): string { return 'bar'; }

    protected function getData(): array
    {
        $months  = collect(range(11, 0))->map(fn ($i) => Carbon::now()->startOfMonth()->subMonths($i));
        $labels  = $months->map(fn ($m) => $m->translatedFormat('M Y'))->toArray();
        $revenue = $months->map(fn ($m) =>
            (float) Appointment::whereYear('appointment_date', $m->year)
                ->whereMonth('appointment_date', $m->month)
                ->whereIn('status', ['confirmed', 'completed'])
                ->sum('price')
        )->toArray();

        return [
            'labels'   => $labels,
            'datasets' => [[
                'label'           => 'Faturação (€)',
                'data'            => $revenue,
                'backgroundColor' => '#8b5e3c',
                'borderColor'     => '#5c4a3a',
                'borderWidth'     => 1,
                'borderRadius'    => 6,
            ]],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['display' => false]],
            'scales'  => ['y' => ['beginAtZero' => true]],
        ];
    }
}
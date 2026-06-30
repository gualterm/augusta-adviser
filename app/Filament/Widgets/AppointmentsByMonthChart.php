<?php

namespace App\Filament\Widgets;

use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Filament\Widgets\ChartWidget;

class AppointmentsByMonthChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    public function getHeading(): string|\Illuminate\Contracts\Support\Htmlable|null
    {
        return '📅 Marcações por Mês (últimos 12 meses)';
    }

    public static function getSort(): int { return 14; }

    protected function getType(): string { return 'line'; }

    protected function getData(): array
    {
        $months    = collect(range(11, 0))->map(fn ($i) => Carbon::now()->startOfMonth()->subMonths($i));
        $labels    = $months->map(fn ($m) => $m->translatedFormat('M Y'))->toArray();
        $total     = $months->map(fn ($m) =>
            Appointment::whereYear('appointment_date', $m->year)
                ->whereMonth('appointment_date', $m->month)
                ->whereIn('status', ['scheduled', 'confirmed', 'completed'])
                ->count()
        )->toArray();
        $confirmed = $months->map(fn ($m) =>
            Appointment::whereYear('appointment_date', $m->year)
                ->whereMonth('appointment_date', $m->month)
                ->whereIn('status', ['confirmed', 'completed'])
                ->count()
        )->toArray();

        return [
            'labels'   => $labels,
            'datasets' => [
                [
                    'label'           => 'Total',
                    'data'            => $total,
                    'borderColor'     => '#6b7280',
                    'backgroundColor' => 'rgba(107,114,128,0.1)',
                    'tension'         => 0.4,
                    'fill'            => true,
                    'pointRadius'     => 3,
                ],
                [
                    'label'           => 'Confirmadas',
                    'data'            => $confirmed,
                    'borderColor'     => '#22c55e',
                    'backgroundColor' => 'rgba(34,197,94,0.12)',
                    'tension'         => 0.4,
                    'fill'            => true,
                    'pointRadius'     => 3,
                ],
            ],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => ['y' => ['beginAtZero' => true, 'ticks' => ['stepSize' => 1]]],
        ];
    }
}
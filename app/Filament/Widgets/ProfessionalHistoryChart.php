<?php

namespace App\Filament\Widgets;

use App\Models\Appointment;
use App\Models\Employee;
use Illuminate\Support\Facades\Auth;
use Filament\Widgets\ChartWidget;

class ProfessionalHistoryChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    public function getHeading(): string|\Illuminate\Contracts\Support\Htmlable|null
    {
        return '👤 Faturação & Marcações por Profissional (histórico)';
    }

    public static function getSort(): int { return 12; }

    protected function getType(): string { return 'bar'; }

    protected function getData(): array
    {
        $employees = Employee::where('active', true)->orderBy('name')->get();
        $bgColors  = ['rgba(92,74,58,0.8)','rgba(34,197,94,0.8)','rgba(59,130,246,0.8)',
                      'rgba(249,115,22,0.8)','rgba(168,85,247,0.8)','rgba(239,68,68,0.8)'];
        $bdColors  = ['#5c4a3a','#22c55e','#3b82f6','#f97316','#a855f7','#ef4444'];

        $counts  = $employees->map(fn ($e) =>
            Appointment::where(fn ($q) =>
                $q->where('employee_id', $e->id)->orWhere('secondary_employee_id', $e->id)
            )->whereIn('status', ['scheduled', 'confirmed', 'completed'])->count()
        )->toArray();

        $revenue = $employees->map(fn ($e) =>
            (float) Appointment::where('employee_id', $e->id)
                ->whereIn('status', ['confirmed', 'completed'])->sum('price')
        )->toArray();

        return [
            'labels'   => $employees->pluck('name')->toArray(),
            'datasets' => [
                [
                    'label'           => 'Total Marcações',
                    'data'            => $counts,
                    'backgroundColor' => array_slice($bgColors, 0, $employees->count()),
                    'borderColor'     => array_slice($bdColors, 0, $employees->count()),
                    'borderWidth'     => 2,
                    'borderRadius'    => 6,
                    'yAxisID'         => 'y',
                ],
                [
                    'label'           => 'Faturação (€)',
                    'data'            => $revenue,
                    'backgroundColor' => 'rgba(34,197,94,0.15)',
                    'borderColor'     => '#22c55e',
                    'borderWidth'     => 2,
                    'type'            => 'line',
                    'tension'         => 0.3,
                    'pointRadius'     => 6,
                    'pointBackgroundColor' => '#22c55e',
                    'yAxisID'         => 'y1',
                ],
            ],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y'  => ['beginAtZero' => true, 'title' => ['display' => true, 'text' => 'Marcações']],
                'y1' => [
                    'beginAtZero' => true,
                    'position'    => 'right',
                    'title'       => ['display' => true, 'text' => 'Faturação (€)'],
                    'grid'        => ['drawOnChartArea' => false],
                ],
            ],
        ];
    }
}
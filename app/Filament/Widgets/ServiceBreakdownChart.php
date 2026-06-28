<?php

namespace App\Filament\Widgets;

use App\Models\Service;
use Filament\Widgets\ChartWidget;

class ServiceBreakdownChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    public function getHeading(): string|\Illuminate\Contracts\Support\Htmlable|null
    {
        return '✂️ Top 10 Serviços Mais Procurados';
    }

    public static function getSort(): int { return 13; }

    protected function getType(): string { return 'doughnut'; }

    protected function getData(): array
    {
        $services = Service::withCount(['appointments' => fn ($q) =>
            $q->whereIn('status', ['scheduled', 'confirmed', 'completed'])
        ])->having('appointments_count', '>', 0)
          ->orderByDesc('appointments_count')
          ->limit(10)
          ->get();

        $palette = [
            '#5c4a3a','#8b5e3c','#c49a6c','#a0522d','#d2691e',
            '#cd853f','#deb887','#f4a460','#d2b48c','#bc8f8f',
        ];

        return [
            'labels'   => $services->pluck('name')->toArray(),
            'datasets' => [[
                'data'            => $services->pluck('appointments_count')->toArray(),
                'backgroundColor' => array_slice($palette, 0, $services->count()),
                'borderWidth'     => 2,
                'borderColor'     => '#fff',
            ]],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['position' => 'bottom', 'labels' => ['boxWidth' => 12, 'font' => ['size' => 11]]],
            ],
            'cutout' => '55%',
        ];
    }
}
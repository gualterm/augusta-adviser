<?php
namespace App\Filament\Widgets;

use App\Models\Appointment;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OdisseiasRevenueStats extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $monthStart = Carbon::now()->startOfMonth()->toDateString();
        $monthEnd   = Carbon::now()->endOfMonth()->toDateString();
        $paid       = ['confirmed', 'completed'];

        $baseQuery = fn () => Appointment::whereBetween('appointment_date', [$monthStart, $monthEnd])
            ->whereIn('status', $paid);

        $fatOdisseias   = (clone $baseQuery())->where('source', 'Odisseias')->sum('price');
        $countOdisseias = (clone $baseQuery())->where('source', 'Odisseias')->count();
        $fatDireto      = (clone $baseQuery())->where('source', '<>', 'Odisseias')->sum('price');
        $countDireto    = (clone $baseQuery())->where('source', '<>', 'Odisseias')->count();

        $total             = $fatOdisseias + $fatDireto;
        $percentOdisseias  = $total > 0 ? round(($fatOdisseias / $total) * 100) : 0;

        return [
            Stat::make('🔗 Faturação Odisseias', '€ ' . number_format((float) $fatOdisseias, 2, ',', '.'))
                ->description("{$countOdisseias} marcações · {$percentOdisseias}% do mês · preço NET ↗")
                ->descriptionIcon('heroicon-m-link')
                ->color('warning')
                ->url('/admin/relatorio-faturacao?tipo=odisseias'),
            Stat::make('🏠 Faturação Direta', '€ ' . number_format((float) $fatDireto, 2, ',', '.'))
                ->description("{$countDireto} marcações · " . (100 - $percentOdisseias) . '% do mês ↗')
                ->descriptionIcon('heroicon-m-home')
                ->color('success')
                ->url('/admin/relatorio-faturacao?tipo=direta'),
        ];
    }
}
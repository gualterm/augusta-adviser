<?php
namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class ActivityStatsWidget extends BaseWidget
{
    protected static ?int $sort = 0;
    protected ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $weekStart  = now()->startOfWeek(\Carbon\Carbon::MONDAY);
        $monthStart = now()->startOfMonth();

        $clientsHoje   = DB::table('clients')->whereDate('created_at', today())->count();
        $clientsSemana = DB::table('clients')->where('created_at', '>=', $weekStart)->count();
        $clientsMes    = DB::table('clients')->where('created_at', '>=', $monthStart)->count();

        $apptHoje   = DB::table('appointments')->whereDate('created_at', today())->count();
        $apptSemana = DB::table('appointments')->where('created_at', '>=', $weekStart)->count();
        $apptMes    = DB::table('appointments')->where('created_at', '>=', $monthStart)->count();

        $porOrigem = DB::table('activity_logs')
            ->select('source', DB::raw('COUNT(*) as total'))
            ->where('event_type', 'appointment.created')
            ->whereDate('created_at', today())
            ->groupBy('source')
            ->pluck('total', 'source');

        $origensHoje = collect([
            'Portal' => $porOrigem->get('portal', 0),
            'Admin'  => $porOrigem->get('admin',  0),
            'Externo'=> $porOrigem->get('external',0),
        ])->filter()->map(fn($v, $k) => "$k: $v")->implode(' · ');

        $canceladasMes = DB::table('appointments')
            ->where('status', 'cancelled')
            ->where('updated_at', '>=', $monthStart)
            ->count();

        return [
            Stat::make('Novos clientes hoje', $clientsHoje)
                ->description("Semana: $clientsSemana · Mês: $clientsMes")
                ->descriptionIcon('heroicon-m-users')
                ->color($clientsHoje > 0 ? 'success' : 'gray'),

            Stat::make('Marcações hoje', $apptHoje)
                ->description($origensHoje ?: "Semana: $apptSemana · Mês: $apptMes")
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color($apptHoje > 0 ? 'primary' : 'gray'),

            Stat::make('Canceladas este mês', $canceladasMes)
                ->description("Semana: $apptSemana · Mês total: $apptMes marcações")
                ->descriptionIcon('heroicon-m-x-circle')
                ->color($canceladasMes > 0 ? 'danger' : 'success'),
        ];
    }
}
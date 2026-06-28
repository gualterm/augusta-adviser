<?php

namespace App\Filament\Widgets;

use App\Models\Appointment;
use App\Models\Client;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AugustaDashboardStats extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $today      = Carbon::today()->toDateString();
        $weekStart  = Carbon::now()->startOfWeek()->toDateString();
        $weekEnd    = Carbon::now()->endOfWeek()->toDateString();
        $monthStart = Carbon::now()->startOfMonth()->toDateString();
        $active     = ['scheduled', 'confirmed', 'completed'];

        $marcacoesHoje   = Appointment::where('appointment_date', $today)->whereIn('status', $active)->count();
        $marcacoesSemana = Appointment::whereBetween('appointment_date', [$weekStart, $weekEnd])->whereIn('status', $active)->count();
        $fatHoje         = Appointment::where('appointment_date', $today)->whereIn('status', ['confirmed', 'completed'])->sum('price');
        $fatMes          = Appointment::whereBetween('appointment_date', [$monthStart, $today])->whereIn('status', ['confirmed', 'completed'])->sum('price');
        $clientesNovos   = Client::whereMonth('created_at', Carbon::now()->month)->whereYear('created_at', Carbon::now()->year)->count();

        return [
            Stat::make('📅 Marcações Hoje', $marcacoesHoje)
                ->description(Carbon::today()->translatedFormat('l, d \d\e F'))
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info'),

            Stat::make('🗓️ Esta Semana', $marcacoesSemana)
                ->description(Carbon::now()->startOfWeek()->format('d/m') . ' – ' . Carbon::now()->endOfWeek()->format('d/m'))
                ->descriptionIcon('heroicon-m-calendar')
                ->color('primary'),

            Stat::make('💶 Faturação Hoje', '€ ' . number_format((float)$fatHoje, 2, ',', '.'))
                ->description('serviços confirmados/concluídos')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('📊 Faturação Mensal', '€ ' . number_format((float)$fatMes, 2, ',', '.'))
                ->description(Carbon::now()->format('F Y'))
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('success'),

            Stat::make('🆕 Novos Clientes', $clientesNovos)
                ->description('registados este mês')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('warning'),

            Stat::make('💰 Total Semanal Equipa', '€ ' . number_format((float)Appointment::whereBetween('appointment_date', [$weekStart, $weekEnd])->whereIn('status', ['confirmed', 'completed'])->sum('price'), 2, ',', '.'))
                ->description($weekStart . ' – ' . $weekEnd . ' · conf. + concl.')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),
        ];
    }
}
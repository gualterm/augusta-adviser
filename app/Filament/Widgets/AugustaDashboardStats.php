<?php
namespace App\Filament\Widgets;

use App\Models\Appointment;
use App\Models\Client;
use App\Models\Employee;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class AugustaDashboardStats extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $user       = Auth::user();
        $role       = $user?->role ?? 'profissional';
        $today      = Carbon::today()->toDateString();
        $weekStart  = Carbon::now()->startOfWeek()->toDateString();
        $weekEnd    = Carbon::now()->endOfWeek()->toDateString();
        $monthStart = Carbon::now()->startOfMonth()->toDateString();
        $active     = ['scheduled', 'confirmed', 'completed'];
        $paid       = ['confirmed', 'completed'];

        // ── Profissional: só as suas marcações + Marta ──────────────
        if ($role === 'profissional') {
            $employee = Employee::where('user_id', $user->id)->first();
            $empIds   = [2]; // Marta sempre incluída
            if ($employee && $employee->id !== 2) {
                $empIds[] = $employee->id;
            }

            $hoje   = Appointment::where('appointment_date', $today)
                ->whereIn('status', $active)
                ->where(fn ($q) => $q->whereIn('employee_id', $empIds)
                                     ->orWhereIn('secondary_employee_id', $empIds))
                ->count();

            $semana = Appointment::whereBetween('appointment_date', [$weekStart, $weekEnd])
                ->whereIn('status', $active)
                ->where(fn ($q) => $q->whereIn('employee_id', $empIds)
                                     ->orWhereIn('secondary_employee_id', $empIds))
                ->count();

            return [
                Stat::make('📅 Marcações Hoje', $hoje)
                    ->description(Carbon::today()->translatedFormat('l, d \d\e F'))
                    ->descriptionIcon('heroicon-m-calendar-days')
                    ->color('info'),
                Stat::make('🗓️ Esta Semana', $semana)
                    ->description(Carbon::now()->startOfWeek()->format('d/m') . ' – ' . Carbon::now()->endOfWeek()->format('d/m'))
                    ->descriptionIcon('heroicon-m-calendar')
                    ->color('primary'),
            ];
        }

        // ── Admin + Recepcionista: contagens gerais ──────────────────
        $marcacoesHoje   = Appointment::where('appointment_date', $today)->whereIn('status', $active)->count();
        $marcacoesSemana = Appointment::whereBetween('appointment_date', [$weekStart, $weekEnd])->whereIn('status', $active)->count();
        $clientesNovos   = Client::whereMonth('created_at', Carbon::now()->month)
                                  ->whereYear('created_at', Carbon::now()->year)->count();

        $stats = [
            Stat::make('📅 Marcações Hoje', $marcacoesHoje)
                ->description(Carbon::today()->translatedFormat('l, d \d\e F'))
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info'),
            Stat::make('🗓️ Esta Semana', $marcacoesSemana)
                ->description(Carbon::now()->startOfWeek()->format('d/m') . ' – ' . Carbon::now()->endOfWeek()->format('d/m'))
                ->descriptionIcon('heroicon-m-calendar')
                ->color('primary'),
            Stat::make('🆕 Novos Clientes', $clientesNovos)
                ->description('registados este mês')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('warning'),
        ];

        // ── Admin: adiciona stats financeiras ────────────────────────
        if ($role === 'admin') {
            $fatHoje   = Appointment::where('appointment_date', $today)->whereIn('status', $paid)->sum('price');
            $fatMes    = Appointment::whereBetween('appointment_date', [$monthStart, $today])->whereIn('status', $paid)->sum('price');
            $fatSemana = Appointment::whereBetween('appointment_date', [$weekStart, $weekEnd])->whereIn('status', $paid)->sum('price');

            $stats = array_merge($stats, [
                Stat::make('💶 Faturação Hoje', '€ ' . number_format((float) $fatHoje, 2, ',', '.'))
                    ->description('serviços confirmados/concluídos')
                    ->descriptionIcon('heroicon-m-check-circle')
                    ->color('success'),
                Stat::make('📊 Faturação Mensal', '€ ' . number_format((float) $fatMes, 2, ',', '.'))
                    ->description(Carbon::now()->format('F Y'))
                    ->descriptionIcon('heroicon-m-chart-bar')
                    ->color('success'),
                Stat::make('💰 Total Semanal Equipa', '€ ' . number_format((float) $fatSemana, 2, ',', '.'))
                    ->description($weekStart . ' – ' . $weekEnd . ' · conf. + concl.')
                    ->descriptionIcon('heroicon-m-user-group')
                    ->color('primary'),
            ]);
        }

        return $stats;
    }
}
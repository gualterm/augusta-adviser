<?php
namespace App\Filament\Pages;

use App\Filament\Widgets\AugustaDashboardStats;
use App\Filament\Widgets\TodayAppointmentsWidget;
use App\Filament\Widgets\WeeklyByProfessionalWidget;
use App\Filament\Widgets\RevenueByWeekChart;
use App\Filament\Widgets\RevenueByMonthChart;
use App\Filament\Widgets\ProfessionalHistoryChart;
use App\Filament\Widgets\ServiceBreakdownChart;
use App\Filament\Widgets\AppointmentsByMonthChart;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\Auth;

class Dashboard extends BaseDashboard
{
    public function getWidgets(): array
    {
        $role = Auth::user()?->role ?? 'profissional';

        if ($role === 'admin') {
            return [
                AugustaDashboardStats::class,
                TodayAppointmentsWidget::class,
                WeeklyByProfessionalWidget::class,
                RevenueByWeekChart::class,
                RevenueByMonthChart::class,
                ProfessionalHistoryChart::class,
                ServiceBreakdownChart::class,
                AppointmentsByMonthChart::class,
            ];
        }

        // recepcionista + profissional: sem gráficos financeiros
        return [
            AugustaDashboardStats::class,
            TodayAppointmentsWidget::class,
            WeeklyByProfessionalWidget::class,
        ];
    }
}
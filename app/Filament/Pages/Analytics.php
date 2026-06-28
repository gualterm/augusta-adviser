<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AppointmentsByMonthChart;
use App\Filament\Widgets\ProfessionalHistoryChart;
use App\Filament\Widgets\RevenueByMonthChart;
use App\Filament\Widgets\ServiceBreakdownChart;
use Filament\Pages\Page;

class Analytics extends Page
{
    // $view não pode ser static em Filament v4
    protected string $view = 'filament.pages.analytics';

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-chart-bar';
    }

    public static function getNavigationLabel(): string
    {
        return 'Análise & Histórico';
    }

    public static function getNavigationSort(): ?int
    {
        return 5;
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return 'Análise & Histórico';
    }

    public function getWidgets(): array
    {
        return [
            RevenueByMonthChart::class,
            AppointmentsByMonthChart::class,
            ServiceBreakdownChart::class,
            ProfessionalHistoryChart::class,
        ];
    }

    public function getColumns(): int|string|array
    {
        return 2;
    }
}
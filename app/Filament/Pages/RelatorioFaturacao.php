<?php
namespace App\Filament\Pages;

use App\Models\Appointment;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class RelatorioFaturacao extends Page
{
    protected string $view = 'filament.pages.relatorio-faturacao';
    protected static ?string $navigationLabel = 'Relatório Faturação';
    protected static bool $shouldRegisterNavigation = false;

    public string $tipo = 'hoje';

    protected $queryString = ['tipo'];

    public function mount(): void
    {
        abort_unless(Auth::user()?->role === 'admin', 403);
    }

    public function getTitulo(): string
    {
        return match($this->tipo) {
            'hoje'      => 'Faturação de Hoje — ' . Carbon::today()->translatedFormat('l, d \de F \de Y'),
            'semana'    => 'Faturação Semanal — ' . Carbon::now()->startOfWeek()->format('d/m') . ' a ' . Carbon::now()->endOfWeek()->format('d/m'),
            'mes'       => 'Faturação Mensal — ' . Carbon::now()->translatedFormat('F Y'),
            'odisseias' => 'Faturação Odisseias — ' . Carbon::now()->translatedFormat('F Y'),
            'direta'    => 'Faturação Direta — ' . Carbon::now()->translatedFormat('F Y'),
            default     => 'Relatório de Faturação',
        };
    }

    public function getData(): array
    {
        $paid       = ['confirmed', 'completed'];
        $monthStart = Carbon::now()->startOfMonth()->toDateString();
        $monthEnd   = Carbon::now()->endOfMonth()->toDateString();

        $query = Appointment::with(['client', 'employee', 'service'])
            ->whereIn('status', $paid)
            ->orderBy('appointment_date')
            ->orderBy('appointment_time');

        match($this->tipo) {
            'hoje'      => $query->where('appointment_date', Carbon::today()->toDateString()),
            'semana'    => $query->whereBetween('appointment_date', [
                               Carbon::now()->startOfWeek()->toDateString(),
                               Carbon::now()->endOfWeek()->toDateString(),
                           ]),
            'mes'       => $query->whereBetween('appointment_date', [$monthStart, $monthEnd]),
            'odisseias' => $query->whereBetween('appointment_date', [$monthStart, $monthEnd])
                                 ->where('source', 'Odisseias'),
            'direta'    => $query->whereBetween('appointment_date', [$monthStart, $monthEnd])
                                 ->where('source', '<>', 'Odisseias'),
            default     => null,
        };

        $appointments = $query->get();

        return [
            'appointments' => $appointments,
            'total'        => (float) $appointments->sum('price'),
            'count'        => $appointments->count(),
        ];
    }
}
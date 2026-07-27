<?php
namespace App\Filament\Pages;

use App\Models\Appointment;
use App\Models\Employee;
use Filament\Pages\Page;

class PagamentosDiarios extends Page
{
    protected string $view = 'filament.pages.pagamentos-diarios';

    public string $selectedDate = '';

    public static function getNavigationIcon(): string { return 'heroicon-o-banknotes'; }
    public static function getNavigationLabel(): string { return 'Pagamentos Diários'; }
    public static function getNavigationGroup(): ?string { return 'Operações'; }
    public static function getNavigationSort(): ?int { return 6; }
    public function getTitle(): string { return 'Pagamentos Diários'; }

    public function mount(): void
    {
        $this->selectedDate = today()->toDateString();
    }

    public function getData(): array
    {
        $date      = $this->selectedDate ?: today()->toDateString();
        $employees = Employee::where('active', true)
            ->with('commissions')
            ->orderBy('name')
            ->get();

        $result = [];
        foreach ($employees as $emp) {
            $appts = Appointment::where('appointment_date', $date)
                ->where('employee_id', $emp->id)
                ->whereIn('status', ['confirmed', 'completed'])
                ->with('service')
                ->orderBy('appointment_time')
                ->get();

            if ($appts->isEmpty()) continue;

            $totalValue = $totalCommission = 0;
            $breakdown  = [];

            foreach ($appts as $appt) {
                $category   = $appt->service->category ?? null;
                $pct        = $emp->commissionPercentageFor($category);
                $commission = round((float) $appt->price * $pct / 100, 2);
                $totalValue      += (float) $appt->price;
                $totalCommission += $commission;
                $breakdown[] = [
                    'time'       => $appt->appointment_time,
                    'service'    => $appt->service->name ?? 'desconhecido',
                    'category'   => $category ?? '—',
                    'price'      => (float) $appt->price,
                    'pct'        => $pct,
                    'commission' => $commission,
                ];
            }

            $result[] = [
                'name'             => $emp->name,
                'count'            => $appts->count(),
                'total_value'      => $totalValue,
                'total_commission' => $totalCommission,
                'breakdown'        => $breakdown,
            ];
        }
        return $result;
    }
}
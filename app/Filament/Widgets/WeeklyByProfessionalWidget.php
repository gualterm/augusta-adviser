<?php

namespace App\Filament\Widgets;

use App\Models\Appointment;
use App\Models\Employee;
use Carbon\Carbon;
use Filament\Widgets\Widget;

class WeeklyByProfessionalWidget extends Widget
{
    protected static ?int $sort = 3;
    protected int|string|array $columnSpan = 'full';
    protected string $view = 'filament.widgets.weekly-by-professional';

    public function getViewData(): array
    {
        $weekStart = Carbon::now()->startOfWeek()->toDateString();
        $weekEnd   = Carbon::now()->endOfWeek()->toDateString();

        $employees = Employee::where('active', true)->orderBy('name')->get();

        $data = $employees->map(function ($emp) use ($weekStart, $weekEnd) {
            $appts = Appointment::where('appointment_date', '>=', $weekStart)
                ->where('appointment_date', '<=', $weekEnd)
                ->where(function ($q) use ($emp) {
                    $q->where('employee_id', $emp->id)
                      ->orWhere('secondary_employee_id', $emp->id);
                })
                ->whereIn('status', ['scheduled', 'confirmed', 'completed'])
                ->get();

            return [
                'name'      => $emp->name,
                'count'     => $appts->count(),
                'revenue'   => $appts->where('employee_id', $emp->id)->sum('price'),
                'confirmed' => $appts->whereIn('status', ['confirmed', 'completed'])->count(),
            ];
        })->filter(fn ($d) => $d['count'] > 0);

        return [
            'data'      => $data,
            'weekStart' => Carbon::parse($weekStart)->format('d/m'),
            'weekEnd'   => Carbon::parse($weekEnd)->format('d/m'),
        ];
    }
}
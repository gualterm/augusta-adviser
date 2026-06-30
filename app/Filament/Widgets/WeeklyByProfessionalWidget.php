<?php
namespace App\Filament\Widgets;

use App\Models\Appointment;
use App\Models\Employee;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class WeeklyByProfessionalWidget extends Widget
{
    protected static ?int $sort = 3;
    protected int|string|array $columnSpan = 'full';
    protected string $view = 'filament.widgets.weekly-by-professional';

    public function getViewData(): array
    {
        $user      = Auth::user();
        $role      = $user?->role ?? 'profissional';
        $weekStart = Carbon::now()->startOfWeek()->toDateString();
        $weekEnd   = Carbon::now()->endOfWeek()->toDateString();

        $employees = Employee::where('active', true)->orderBy('name')->get();

        // Profissional: só vê a si próprio e Marta
        if ($role === 'profissional') {
            $employee  = Employee::where('user_id', $user->id)->first();
            $empIds    = [2];
            if ($employee && $employee->id !== 2) $empIds[] = $employee->id;
            $employees = $employees->whereIn('id', $empIds);
        }

        $data = $employees->map(function ($emp) use ($weekStart, $weekEnd) {
            $appts = Appointment::where('appointment_date', '>=', $weekStart)
                ->where('appointment_date', '<=', $weekEnd)
                ->where(fn ($q) => $q->where('employee_id', $emp->id)
                                     ->orWhere('secondary_employee_id', $emp->id))
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
            'data'        => $data,
            'weekStart'   => Carbon::parse($weekStart)->format('d/m'),
            'weekEnd'     => Carbon::parse($weekEnd)->format('d/m'),
            'showRevenue' => ($role === 'admin'),
        ];
    }
}
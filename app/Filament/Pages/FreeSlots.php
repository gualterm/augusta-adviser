<?php
namespace App\Filament\Pages;

use App\Models\Appointment;
use App\Models\BusinessHour;
use App\Models\Employee;
use Carbon\Carbon;
use Filament\Pages\Page;

class FreeSlots extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-clock';
    protected static ?string $navigationLabel = 'Horários Livres';
    protected static ?string $title           = 'Horários Livres';
    protected static $navigationGroup         = 'Operações';
    protected static ?int    $navigationSort  = 5;
    protected string         $view            = 'filament.pages.free-slots';

    public int $daysAhead  = 7;
    public int $dayOffset  = 0;

    public function getFreeSlots(): array
    {
        $startDay = Carbon::today()->addWeeks($this->dayOffset);
        $endDay   = $startDay->copy()->addDays($this->daysAhead - 1);

        $employees = Employee::query()
            ->where('active', true)
            ->whereHas('user', fn ($q) => $q->where('role', '!=', 'recepcionista'))
            ->orderBy('name')
            ->get();

        $days = [];

        for ($d = $startDay->copy(); $d->lte($endDay); $d->addDay()) {
            $date      = $d->format('Y-m-d');
            $dayOfWeek = (int) $d->format('w');

            $bh = BusinessHour::where('day_of_week', $dayOfWeek)->first();
            if (!$bh || !$bh->is_open) continue;

            $dayOpen  = Carbon::parse($date . ' ' . $bh->open_time);
            $dayClose = Carbon::parse($date . ' ' . $bh->close_time);

            $appointments = Appointment::query()
                ->whereIn('employee_id', $employees->pluck('id'))
                ->where('appointment_date', $date)
                ->where('status', '!=', 'cancelled')
                ->whereNotNull('appointment_time')
                ->whereNotNull('end_time')
                ->get();

            $slots  = [];
            $cursor = $dayOpen->copy();

            while ($cursor->lt($dayClose)) {
                $slotStart = $cursor->copy();
                $slotEnd   = $cursor->copy()->addMinutes(30);

                $freeEmployees = $employees->filter(function ($emp) use ($appointments, $slotStart, $slotEnd) {
                    foreach ($appointments->where('employee_id', $emp->id) as $appt) {
                        $aStart = Carbon::parse($appt->appointment_date . ' ' . $appt->appointment_time);
                        $aEnd   = Carbon::parse($appt->appointment_date . ' ' . $appt->end_time);
                        if ($slotStart->lt($aEnd) && $slotEnd->gt($aStart)) return false;
                    }
                    return true;
                })->values();

                if ($freeEmployees->isNotEmpty()) {
                    $createUrl = \App\Filament\Resources\Appointments\AppointmentResource::getUrl('create')
                        . '?' . http_build_query([
                            'appointment_date' => $date,
                            'appointment_time' => $slotStart->format('H:i'),
                        ]);

                    $slots[] = [
                        'time'          => $slotStart->format('H:i'),
                        'freeEmployees' => $freeEmployees->pluck('name')->toArray(),
                        'total'         => $freeEmployees->count(),
                        'createUrl'     => $createUrl,
                    ];
                }

                $cursor->addMinutes(30);
            }

            if (!empty($slots)) {
                $days[] = [
                    'date'       => $date,
                    'label'      => $d->translatedFormat('l, d \de F'),
                    'shortDay'   => $d->translatedFormat('D d/m'),
                    'isToday'    => $d->isToday(),
                    'isTomorrow' => $d->isTomorrow(),
                    'slots'      => $slots,
                    'total'      => count($slots),
                ];
            }
        }

        return $days;
    }
}

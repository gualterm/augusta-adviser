<?php
namespace App\Filament\Pages;

use App\Models\Appointment;
use App\Models\BusinessHour;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use Carbon\Carbon;
use Filament\Pages\Page;

class FreeSlots extends Page
{
    protected string $view = 'filament.pages.free-slots';
    public int $daysAhead  = 7;
    public int $dayOffset  = 0;

    public static function getNavigationIcon(): string { return 'heroicon-o-clock'; }
    public static function getNavigationLabel(): string { return 'Horários Livres'; }
    public static function getNavigationGroup(): string { return 'Operações'; }
    public static function getNavigationSort(): int { return 5; }
    public function getTitle(): string { return 'Horários Livres'; }

    public function getFreeSlots(): array
    {
        $startDay = Carbon::today()->addWeeks($this->dayOffset);
        $endDay   = $startDay->copy()->addDays($this->daysAhead - 1);
        $employees = Employee::query()->where('active', true)
            ->whereHas('user', fn ($q) => $q->where('role', '!=', 'rececionista'))
            ->orderBy('name')->get();
        // Horário individual de cada profissional, indexado por "empId-diaDaSemana",
        // para não mostrar como livre quem não trabalha nesse dia/hora.
        // Pedido da Marta (2026-07-06): Horários Livres estava só a olhar para
        // conflitos de marcações, ignorando por completo o horário de trabalho.
        $schedules = EmployeeSchedule::whereIn('employee_id', $employees->pluck('id'))
            ->get()
            ->keyBy(fn ($sch) => $sch->employee_id . '-' . $sch->day_of_week);
        $days = [];
        for ($d = $startDay->copy(); $d->lte($endDay); $d->addDay()) {
            $date = $d->format('Y-m-d');
            $dayOfWeek = (int) $d->format('w');
            $bh = BusinessHour::where('day_of_week', $dayOfWeek)->first();
            if (!$bh || !$bh->is_open) continue;
            $dayOpen  = Carbon::parse($date . ' ' . $bh->open_time);
            $dayClose = Carbon::parse($date . ' ' . $bh->close_time);
            $appts = Appointment::whereIn('employee_id', $employees->pluck('id'))
                ->where('appointment_date', $date)->where('status', '!=', 'cancelled')
                ->whereNotNull('appointment_time')->whereNotNull('end_time')->get();
            $slots = [];
            for ($cur = $dayOpen->copy(); $cur->lt($dayClose); $cur->addMinutes(30)) {
                $s = $cur->copy(); $e = $cur->copy()->addMinutes(30);
                $free = $employees->filter(function($emp) use ($appts,$s,$e,$schedules,$date,$dayOfWeek) {
                    // Respeitar o horário individual do profissional, se estiver definido.
                    $sch = $schedules->get($emp->id . '-' . $dayOfWeek);
                    if ($sch) {
                        if (!$sch->is_working) return false;
                        $empStart = Carbon::parse($date . ' ' . $sch->start_time);
                        $empEnd   = Carbon::parse($date . ' ' . $sch->end_time);
                        if ($s->lt($empStart) || $e->gt($empEnd)) return false;
                    }
                    foreach ($appts->where('employee_id',$emp->id) as $a) {
                        $d2 = Carbon::parse($a->appointment_date)->toDateString();
                        $as = Carbon::parse($d2.' '.$a->appointment_time);
                        $ae = Carbon::parse($d2.' '.$a->end_time);
                        if ($s->lt($ae) && $e->gt($as)) return false;
                    }
                    return true;
                })->values();
                if ($free->isNotEmpty()) {
                    $slots[] = ['time'=>$s->format('H:i'),'freeEmployees'=>$free->pluck('name')->toArray(),'total'=>$free->count(),
                        'createUrl'=>\App\Filament\Resources\Appointments\AppointmentResource::getUrl('create').'?'.http_build_query(['appointment_date'=>$date,'appointment_time'=>$s->format('H:i')])];
                }
            }
            if (!empty($slots)) $days[] = ['date'=>$date,'shortDay'=>$d->translatedFormat('D d/m'),'isToday'=>$d->isToday(),'isTomorrow'=>$d->isTomorrow(),'slots'=>$slots,'total'=>count($slots)];
        }
        return $days;
    }
}

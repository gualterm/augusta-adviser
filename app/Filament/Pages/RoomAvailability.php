<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Appointments\AppointmentResource;
use App\Models\Appointment;
use App\Models\Employee;
use App\Models\Equipment;
use App\Models\Workstation;
use App\Services\AppointmentConflictService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use UnitEnum;

class RoomAvailability extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Disponibilidade';

    protected static ?string $title = 'Disponibilidade do Dia';

    protected static string|UnitEnum|null $navigationGroup = 'Operações';

    protected string $view = 'filament.pages.room-availability';

    public ?string $date     = null;
    public string $viewMode  = 'day'; // 'day' | 'week'
    public int    $weekOffset = 0;

    /**
     * Paleta de cores para identificar visualmente cada equipamento.
     */
    protected const EQUIPMENT_COLORS = [
        '#9b6bd1',
        '#3f8fc4',
        '#c47f3f',
        '#5fa888',
        '#c45f8f',
        '#7a8fc4',
    ];

    /**
     * Paleta de cores para identificar visualmente cada profissional.
     */
    protected const EMPLOYEE_COLORS = [
        '#cf6b6b',
        '#6bb0a8',
        '#c4a13f',
        '#7c8fd1',
        '#a86bc4',
        '#5fa86b',
    ];

    public function mount(): void
    {
        $this->date = $this->date ?: now()->format('Y-m-d');
    }

    public function previousWeek(): void
    {
        $this->weekOffset--;
    }

    public function nextWeek(): void
    {
        $this->weekOffset++;
    }

    public function thisWeek(): void
    {
        $this->weekOffset = 0;
    }

    public function getWorkstationsWithAppointments(): Collection
    {
        $date = $this->date ?: now()->format('Y-m-d');

        return Workstation::query()
            ->where('active', true)
            ->orderByRaw("CASE WHEN type = 'marquesa' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get()
            ->map(function (Workstation $workstation) use ($date) {
                $appointments = Appointment::query()
                    ->where('workstation_id', $workstation->id)
                    ->where('appointment_date', $date)
                    ->where('status', '!=', 'cancelled')
                    ->whereNotNull('appointment_time')
                    ->whereNotNull('end_time')
                    ->with(['client', 'employee', 'service'])
                    ->orderBy('appointment_time')
                    ->get()
                    ->map(function (Appointment $appointment) use ($workstation, $date) {
                        $appointment->setAttribute(
                            'isOverlapping',
                            AppointmentConflictService::workstationHasConflict(
                                $workstation->id,
                                $date,
                                $appointment->appointment_time,
                                $appointment->end_time,
                                $appointment->id
                            )
                        );

                        $appointment->setAttribute(
                            'editUrl',
                            AppointmentResource::getUrl('edit', ['record' => $appointment])
                        );

                        return $appointment;
                    });

                $workstation->setAttribute('dayAppointments', $appointments);
                $workstation->setAttribute(
                    'createUrl',
                    AppointmentResource::getUrl('create') . '?' . http_build_query([
                        'workstation_id' => $workstation->id,
                        'appointment_date' => $date,
                    ])
                );

                return $workstation;
            });
    }

    public function getEquipmentWithAppointments(): Collection
    {
        $date = $this->date ?: now()->format('Y-m-d');

        return Equipment::query()
            ->where('active', true)
            ->orderBy('name')
            ->get()
            ->values()
            ->map(function (Equipment $equipment, int $index) use ($date) {
                $appointments = Appointment::query()
                    ->where('appointment_date', $date)
                    ->where('status', '!=', 'cancelled')
                    ->whereNotNull('appointment_time')
                    ->whereNotNull('end_time')
                    ->whereHas('service.equipment', function ($query) use ($equipment) {
                        $query->where('equipment.id', $equipment->id);
                    })
                    ->with(['client', 'employee', 'service', 'workstation'])
                    ->orderBy('appointment_time')
                    ->get()
                    ->map(function (Appointment $appointment) use ($equipment, $date) {
                        $overlapping = Appointment::query()
                            ->where('id', '!=', $appointment->id)
                            ->where('appointment_date', $date)
                            ->whereNotNull('appointment_time')
                            ->whereNotNull('end_time')
                            ->whereHas('service.equipment', function ($query) use ($equipment) {
                                $query->where('equipment.id', $equipment->id);
                            })
                            ->where(function ($query) use ($appointment) {
                                $query
                                    ->whereBetween('appointment_time', [$appointment->appointment_time, $appointment->end_time])
                                    ->orWhereBetween('end_time', [$appointment->appointment_time, $appointment->end_time])
                                    ->orWhere(function ($query) use ($appointment) {
                                        $query
                                            ->where('appointment_time', '<=', $appointment->appointment_time)
                                            ->where('end_time', '>=', $appointment->end_time);
                                    });
                            })
                            ->count();

                        // +1 para contar a própria marcação a par das que se sobrepõem
                        $appointment->setAttribute(
                            'isOverlapping',
                            ($overlapping + 1) > $equipment->quantity
                        );

                        $appointment->setAttribute(
                            'editUrl',
                            AppointmentResource::getUrl('edit', ['record' => $appointment])
                        );

                        return $appointment;
                    });

                $equipment->setAttribute('dayAppointments', $appointments);
                $equipment->setAttribute(
                    'color',
                    self::EQUIPMENT_COLORS[$index % count(self::EQUIPMENT_COLORS)]
                );

                return $equipment;
            });
    }

    public function getEmployeesWithAppointments(): Collection
    {
        $date = $this->date ?: now()->format('Y-m-d');

        return Employee::query()
            ->where('active', true)
            ->whereHas('user', fn ($q) => $q->where('role', '!=', 'rececionista'))
            ->orderBy('name')
            ->get()
            ->values()
            ->map(function (Employee $employee, int $index) use ($date) {
                $appointments = Appointment::query()
                    ->where('employee_id', $employee->id)
                    ->where('appointment_date', $date)
                    ->where('status', '!=', 'cancelled')
                    ->whereNotNull('appointment_time')
                    ->whereNotNull('end_time')
                    ->with(['client', 'service', 'workstation'])
                    ->orderBy('appointment_time')
                    ->get()
                    ->map(function (Appointment $appointment) use ($employee, $date) {
                        $appointment->setAttribute(
                            'isOverlapping',
                            ($appointment->appointment_time && $appointment->end_time)
                                ? AppointmentConflictService::employeeHasConflict(
                                    $employee->id,
                                    $date,
                                    $appointment->appointment_time,
                                    $appointment->end_time,
                                    $appointment->id
                                )
                                : false
                        );

                        $appointment->setAttribute(
                            'editUrl',
                            AppointmentResource::getUrl('edit', ['record' => $appointment])
                        );

                        return $appointment;
                    });

                $employee->setAttribute('dayAppointments', $appointments);
                $employee->setAttribute(
                    'createUrl',
                    AppointmentResource::getUrl('create') . '?' . http_build_query([
                        'employee_id' => $employee->id,
                        'appointment_date' => $date,
                    ])
                );
                $employee->setAttribute(
                    'color',
                    self::EMPLOYEE_COLORS[$index % count(self::EMPLOYEE_COLORS)]
                );

                return $employee;
            });
    }
    /**
     * Linha de tempo por profissional: blocos livres e ocupados.
     * Usa o horário da loja para definir a janela do dia.
     */
    public function getEmployeeTimeline(): \Illuminate\Support\Collection
    {
        $date      = $this->date ?: now()->format('Y-m-d');
        $dayOfWeek = (int) \Carbon\Carbon::parse($date)->format('w'); // 0=Dom

        // Horário da loja para este dia
        $bh = \App\Models\BusinessHour::where('day_of_week', $dayOfWeek)->first();

        if (!$bh || !$bh->is_open) {
            return collect(); // loja fechada
        }

        $dayStart    = \Carbon\Carbon::parse($date . ' ' . $bh->open_time);
        $dayEnd      = \Carbon\Carbon::parse($date . ' ' . $bh->close_time);
        $totalMinutes = $dayStart->diffInMinutes($dayEnd);

        if ($totalMinutes <= 0) return collect();

        return Employee::query()
            ->where('active', true)
            ->whereHas('user', fn ($q) => $q->where('role', '!=', 'rececionista'))
            ->orderBy('name')
            ->get()
            ->values()
            ->map(function (Employee $employee, int $index) use ($date, $dayStart, $dayEnd, $totalMinutes) {
                $appointments = Appointment::query()
                    ->where('employee_id', $employee->id)
                    ->where('appointment_date', $date)
                    ->where('status', '!=', 'cancelled')
                    ->whereNotNull('appointment_time')
                    ->whereNotNull('end_time')
                    ->with(['client', 'service'])
                    ->orderBy('appointment_time')
                    ->get();

                $blocks = [];
                $cursor = $dayStart->copy();

                foreach ($appointments as $appt) {
                    $apptStart = \Carbon\Carbon::parse($date . ' ' . $appt->appointment_time);
                    $apptEnd   = \Carbon\Carbon::parse($date . ' ' . $appt->end_time);

                    // ajustar ao horário da loja
                    if ($apptStart->lt($dayStart)) $apptStart = $dayStart->copy();
                    if ($apptEnd->gt($dayEnd))     $apptEnd   = $dayEnd->copy();
                    if ($apptEnd->lte($apptStart)) continue;

                    // slot livre antes desta marcação
                    if ($cursor->lt($apptStart)) {
                        $freeMins = $cursor->diffInMinutes($apptStart);
                        $blocks[] = [
                            'type'      => 'free',
                            'start'     => $cursor->format('H:i'),
                            'end'       => $apptStart->format('H:i'),
                            'pct'       => round($freeMins / $totalMinutes * 100, 2),
                            'createUrl' => \App\Filament\Resources\Appointments\AppointmentResource::getUrl('create') . '?' . http_build_query([
                                'employee_id'      => $employee->id,
                                'appointment_date' => $date,
                                'appointment_time' => $cursor->format('H:i'),
                            ]),
                        ];
                    }

                    // bloco ocupado
                    $busyMins = $apptStart->diffInMinutes($apptEnd);
                    $blocks[] = [
                        'type'    => 'busy',
                        'start'   => $apptStart->format('H:i'),
                        'end'     => $apptEnd->format('H:i'),
                        'pct'     => round($busyMins / $totalMinutes * 100, 2),
                        'client'  => $appt->client?->name ?? '—',
                        'service' => $appt->service?->name ?? '—',
                        'editUrl' => \App\Filament\Resources\Appointments\AppointmentResource::getUrl('edit', ['record' => $appt]),
                    ];

                    $cursor = $apptEnd->copy();
                }

                // slot livre final
                if ($cursor->lt($dayEnd)) {
                    $freeMins = $cursor->diffInMinutes($dayEnd);
                    $blocks[] = [
                        'type'      => 'free',
                        'start'     => $cursor->format('H:i'),
                        'end'       => $dayEnd->format('H:i'),
                        'pct'       => round($freeMins / $totalMinutes * 100, 2),
                        'createUrl' => \App\Filament\Resources\Appointments\AppointmentResource::getUrl('create') . '?' . http_build_query([
                            'employee_id'      => $employee->id,
                            'appointment_date' => $date,
                            'appointment_time' => $cursor->format('H:i'),
                        ]),
                    ];
                }

                return [
                    'name'     => $employee->name,
                    'color'    => self::EMPLOYEE_COLORS[$index % count(self::EMPLOYEE_COLORS)],
                    'dayStart' => $dayStart->format('H:i'),
                    'dayEnd'   => $dayEnd->format('H:i'),
                    'blocks'   => $blocks,
                ];
            });
    }
    /**
     * Vista semanal: para cada profissional (não-recepcionista),
     * mostra os 7 dias da semana com marcações e slots livres.
     */
    public function getWeeklyTimeline(): array
    {
        $weekStart = \Carbon\Carbon::now()->startOfWeek(\Carbon\Carbon::MONDAY)->addWeeks($this->weekOffset);
        $days      = [];
        for ($i = 0; $i < 7; $i++) {
            $days[] = $weekStart->copy()->addDays($i);
        }

        $employees = Employee::query()
            ->where('active', true)
            ->whereHas('user', fn ($q) => $q->where('role', '!=', 'rececionista'))
            ->orderBy('name')
            ->get()
            ->values();

        $result = [];
        foreach ($employees as $index => $employee) {
            $color    = self::EMPLOYEE_COLORS[$index % count(self::EMPLOYEE_COLORS)];
            $weekData = [];

            foreach ($days as $day) {
                $date      = $day->format('Y-m-d');
                $dayOfWeek = (int) $day->format('w'); // 0=Dom

                $bh     = \App\Models\BusinessHour::where('day_of_week', $dayOfWeek)->first();
                $isOpen = $bh && $bh->is_open;

                $appointments = [];
                if ($isOpen) {
                    $appts = Appointment::query()
                        ->where('employee_id', $employee->id)
                        ->where('appointment_date', $date)
                        ->where('status', '!=', 'cancelled')
                        ->whereNotNull('appointment_time')
                        ->with(['client', 'service'])
                        ->orderBy('appointment_time')
                        ->get();

                    foreach ($appts as $appt) {
                        $appointments[] = [
                            'time'    => substr($appt->appointment_time, 0, 5),
                            'end'     => $appt->end_time ? substr($appt->end_time, 0, 5) : '',
                            'client'  => $appt->client?->name  ?? '—',
                            'service' => $appt->service?->name ?? '—',
                            'editUrl' => \App\Filament\Resources\Appointments\AppointmentResource::getUrl('edit', ['record' => $appt]),
                        ];
                    }
                }

                $createUrl = \App\Filament\Resources\Appointments\AppointmentResource::getUrl('create') . '?' . http_build_query([
                    'employee_id'      => $employee->id,
                    'appointment_date' => $date,
                ]);

                $weekData[] = [
                    'date'         => $date,
                    'label'        => $day->translatedFormat('D d/m'),
                    'isOpen'       => $isOpen,
                    'appointments' => $appointments,
                    'createUrl'    => $createUrl,
                ];
            }

            $result[] = [
                'name'     => $employee->name,
                'color'    => $color,
                'weekData' => $weekData,
            ];
        }

        $fmt = fn ($d) => $d->translatedFormat('D d/m');
        return [
            'employees'      => $result,
            'days'           => array_map($fmt, $days),
            'weekStartLabel' => $days[0]->format('d/m') . ' – ' . $days[6]->format('d/m'),
        ];
    }
}
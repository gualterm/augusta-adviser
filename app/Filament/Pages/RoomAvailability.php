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

    public ?string $date = null;

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
}

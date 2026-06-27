<?php
namespace App\Services;
use App\Models\Appointment;
use App\Models\Equipment;
use App\Models\Workstation;
use Carbon\Carbon;
class AppointmentConflictService
{
    public static function employeeHasConflict(
        ?int $employeeId,
        string $date,
        string $startTime,
        ?string $endTime,
        ?int $ignoreAppointmentId = null
    ): bool {
        if ($employeeId === null || $endTime === null) {
            return false;
        }
        return Appointment::query()
            ->when($ignoreAppointmentId, fn ($q) => $q->where('id', '!=', $ignoreAppointmentId))
            ->where('employee_id', $employeeId)
            ->where('appointment_date', $date)
            ->where(function ($query) use ($startTime, $endTime) {
                $query
                    ->whereBetween('appointment_time', [$startTime, $endTime])
                    ->orWhereBetween('end_time', [$startTime, $endTime])
                    ->orWhere(function ($q) use ($startTime, $endTime) {
                        $q->where('appointment_time', '<=', $startTime)
                          ->where('end_time', '>=', $endTime);
                    });
            })
            ->exists();
    }
    protected static function overlappingWorkstationCount(
        ?int $workstationId,
        string $date,
        string $startTime,
        ?string $endTime,
        ?int $ignoreAppointmentId = null
    ): int {
        if ($workstationId === null || $endTime === null) {
            return 0;
        }
        return Appointment::query()
            ->when($ignoreAppointmentId, fn ($q) => $q->where('id', '!=', $ignoreAppointmentId))
            ->where('workstation_id', $workstationId)
            ->where('appointment_date', $date)
            ->where(function ($query) use ($startTime, $endTime) {
                $query
                    ->whereBetween('appointment_time', [$startTime, $endTime])
                    ->orWhereBetween('end_time', [$startTime, $endTime])
                    ->orWhere(function ($q) use ($startTime, $endTime) {
                        $q->where('appointment_time', '<=', $startTime)
                          ->where('end_time', '>=', $endTime);
                    });
            })
            ->count();
    }
    public static function workstationHasConflict(
        ?int $workstationId,
        string $date,
        string $startTime,
        ?string $endTime,
        ?int $ignoreAppointmentId = null
    ): bool {
        return self::overlappingWorkstationCount($workstationId, $date, $startTime, $endTime, $ignoreAppointmentId) >= 1;
    }
    public static function workstationAtCapacity(
        ?int $workstationId,
        string $date,
        string $startTime,
        ?string $endTime,
        ?int $ignoreAppointmentId = null,
        int $maxOverlap = 2
    ): bool {
        return self::overlappingWorkstationCount($workstationId, $date, $startTime, $endTime, $ignoreAppointmentId) >= $maxOverlap;
    }
    public static function findAlternativeWorkstation(
        string $type,
        string $date,
        string $startTime,
        ?string $endTime,
        ?int $ignoreAppointmentId = null
    ): ?Workstation {
        if ($endTime === null) return null;
        $workstations = Workstation::query()->where('type', $type)->where('active', true)->orderBy('name')->get();
        foreach ($workstations as $workstation) {
            if (! self::workstationHasConflict($workstation->id, $date, $startTime, $endTime, $ignoreAppointmentId)) {
                return $workstation;
            }
        }
        return null;
    }
    public static function equipmentHasConflict(
        array|\Illuminate\Support\Collection $equipmentIds,
        string $date,
        string $startTime,
        ?string $endTime,
        ?int $ignoreAppointmentId = null
    ): bool {
        if ($endTime === null) return false;
        foreach ($equipmentIds as $equipmentId) {
            $equipment = Equipment::find($equipmentId);
            if (! $equipment) continue;
            $usedCount = Appointment::query()
                ->when($ignoreAppointmentId, fn ($q) => $q->where('id', '!=', $ignoreAppointmentId))
                ->where('appointment_date', $date)
                ->whereHas('service.equipment', fn ($q) => $q->where('equipment.id', $equipmentId))
                ->where(function ($query) use ($startTime, $endTime) {
                    $query
                        ->whereBetween('appointment_time', [$startTime, $endTime])
                        ->orWhereBetween('end_time', [$startTime, $endTime])
                        ->orWhere(function ($q) use ($startTime, $endTime) {
                            $q->where('appointment_time', '<=', $startTime)
                              ->where('end_time', '>=', $endTime);
                        });
                })
                ->count();
            if ($usedCount >= $equipment->quantity) return true;
        }
        return false;
    }
    public static function suggestFixedDelaySlot(
        ?int $employeeId,
        string $workstationType,
        string $date,
        string $startTime,
        int $durationMinutes,
        ?int $ignoreAppointmentId = null,
        array $equipmentIds = [],
        int $delayMinutes = 10
    ): ?array {
        $newStart = Carbon::parse($startTime)->addMinutes($delayMinutes);
        $newEnd   = $newStart->copy()->addMinutes($durationMinutes);
        $start    = $newStart->format('H:i');
        $end      = $newEnd->format('H:i');
        if ($employeeId !== null && self::employeeHasConflict($employeeId, $date, $start, $end, $ignoreAppointmentId)) {
            return null;
        }
        if (! empty($equipmentIds) && self::equipmentHasConflict($equipmentIds, $date, $start, $end, $ignoreAppointmentId)) {
            return null;
        }
        $workstation = self::findAlternativeWorkstation($workstationType, $date, $start, $end, $ignoreAppointmentId);
        if (! $workstation) return null;
        return [
            'start'            => $start,
            'end'              => $end,
            'workstation_id'   => $workstation->id,
            'workstation_name' => $workstation->name,
        ];
    }
}
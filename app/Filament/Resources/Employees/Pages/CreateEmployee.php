<?php

namespace App\Filament\Resources\Employees\Pages;

use App\Filament\Resources\Employees\EmployeeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function afterCreate(): void
    {
        $employee = $this->record->fresh();
        foreach ($this->data['schedules'] ?? [] as $s) {
            $employee->schedules()->updateOrCreate(
                ['day_of_week' => (int) $s['day_of_week']],
                [
                    'start_time' => $s['start_time'] ?: null,
                    'end_time'   => $s['end_time']   ?: null,
                    'is_working' => (bool) ($s['is_working'] ?? true),
                ]
            );
        }
    }
}

<?php
namespace App\Filament\Resources\Employees\Pages;
use App\Filament\Resources\Employees\Actions\DeleteEmployeeAction;
use App\Filament\Resources\Employees\EmployeeResource;
use Filament\Resources\Pages\EditRecord;
class EditEmployee extends EditRecord
{
    protected static string $resource = EmployeeResource::class;
    protected function getHeaderActions(): array
    {
        return [DeleteEmployeeAction::make()];
    }
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['schedules'] = $this->record->schedules()
            ->orderBy('day_of_week')
            ->get()
            ->map(fn ($s) => [
                'day_of_week' => $s->day_of_week,
                'start_time'  => substr($s->start_time ?? '', 0, 5),
                'end_time'    => substr($s->end_time   ?? '', 0, 5),
                'is_working'  => (bool) $s->is_working,
            ])
            ->toArray();
        return $data;
    }
    protected function afterSave(): void
    {
        $employee = $this->record->fresh();
        // Sincronizar user
        if ($employee->user_id) {
            $sync = ['phone' => $employee->phone, 'nif' => $employee->nif];
            if ($employee->name)  $sync['name']  = $employee->name;
            if ($employee->email) $sync['email'] = $employee->email;
            $employee->user?->update($sync);
        }
        // Horários via updateOrCreate (evita UniqueConstraintViolation)
        $schedules  = $this->data['schedules'] ?? [];
        $daysInForm = [];
        foreach ($schedules as $s) {
            $employee->schedules()->updateOrCreate(
                ['day_of_week' => (int) $s['day_of_week']],
                [
                    'start_time' => $s['start_time'] ?: null,
                    'end_time'   => $s['end_time']   ?: null,
                    'is_working' => (bool) ($s['is_working'] ?? true),
                ]
            );
            $daysInForm[] = (int) $s['day_of_week'];
        }
        if (! empty($daysInForm)) {
            $employee->schedules()->whereNotIn('day_of_week', $daysInForm)->delete();
        } else {
            $employee->schedules()->delete();
        }
    }
}

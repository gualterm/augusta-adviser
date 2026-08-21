<?php
namespace App\Filament\Resources\Employees\Pages;
use App\Filament\Resources\Employees\Actions\DeleteEmployeeAction;
use App\Filament\Resources\Employees\EmployeeResource;
use App\Models\Employee;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
class EditEmployee extends EditRecord
{
    protected static string $resource = EmployeeResource::class;
    protected function getHeaderActions(): array
    {
        $currentId = $this->getRecord()->id;
        $previousId = Employee::where('id', '<', $currentId)->orderByDesc('id')->value('id');
        $nextId = Employee::where('id', '>', $currentId)->orderBy('id')->value('id');
        return [
            Action::make('previousEmployee')
                ->label('◀ Anterior')
                ->color('gray')
                ->visible((bool) $previousId)
                ->url($previousId ? EmployeeResource::getUrl('edit', ['record' => $previousId]) : null),
            Action::make('nextEmployee')
                ->label('Seguinte ▶')
                ->color('gray')
                ->visible((bool) $nextId)
                ->url($nextId ? EmployeeResource::getUrl('edit', ['record' => $nextId]) : null),
            DeleteEmployeeAction::make(),
        ];
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
        if ($employee->user_id) {
            $sync = ['phone' => $employee->phone, 'nif' => $employee->nif];
            if ($employee->name)  $sync['name']  = $employee->name;
            if ($employee->email) $sync['email'] = $employee->email;
            $employee->user?->update($sync);
        }
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

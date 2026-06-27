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
    protected function afterSave(): void
    {
        $employee = $this->record->fresh();
        if (! $employee->user_id) return;

        $sync = ['phone' => $employee->phone, 'nif' => $employee->nif];

        // Só sincroniza name/email se não forem nulos (user tem email NOT NULL)
        if ($employee->name)  $sync['name']  = $employee->name;
        if ($employee->email) $sync['email'] = $employee->email;

        $employee->user?->update($sync);
    }
}
<?php
namespace App\Filament\Resources\Users\Pages;
use App\Filament\Resources\Users\UserResource;
use App\Models\Employee;
use Filament\Resources\Pages\CreateRecord;
class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
    protected function afterCreate(): void
    {
        if ($this->record->role !== 'profissional') return;
        $state = $this->form->getRawState();
        Employee::updateOrCreate(
            ['user_id' => $this->record->id],
            [
                'name'   => $this->record->name,
                'email'  => $this->record->email,
                'phone'  => $this->record->phone,
                'nif'    => $this->record->nif,
                'role'   => $state['_specialty'] ?? null,
                'default_commission_percentage' => $state['_commission'] ?? 0,
                'active' => (bool) ($state['_active'] ?? true),
            ]
        );
    }
}
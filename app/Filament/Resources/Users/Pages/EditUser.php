<?php
namespace App\Filament\Resources\Users\Pages;
use App\Filament\Resources\Users\UserResource;
use App\Models\Employee;
use Filament\Resources\Pages\EditRecord;
class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $employee = $this->record->employee;
        if ($employee) {
            $data['_specialty']  = $employee->role;
            $data['_commission'] = $employee->default_commission_percentage;
            $data['_active']     = (bool) $employee->active;
        }
        return $data;
    }
    protected function afterSave(): void
    {
        $user  = $this->record->fresh();
        $state = $this->form->getRawState();

        // Cria employee novo apenas se role=profissional e ainda não tem
        if ($user->role === 'profissional' && ! $user->employee) {
            Employee::create([
                'user_id' => $user->id,
                'name'    => $user->name,
                'email'   => $user->email,
                'phone'   => $user->phone,
                'nif'     => $user->nif,
                'role'    => $state['_specialty'] ?? null,
                'default_commission_percentage' => $state['_commission'] ?? 0,
                'active'  => (bool) ($state['_active'] ?? true),
            ]);
            return;
        }

        // Se já tem employee ligado — sincroniza SEMPRE (independente do role)
        $employee = $user->employee;
        if (! $employee) return;

        $sync = [
            'name'  => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'nif'   => $user->nif,
        ];

        // Especialidade e comissão só se vier da secção profissional
        if ($user->role === 'profissional') {
            $sync['role']                          = $state['_specialty'] ?? $employee->role;
            $sync['default_commission_percentage'] = $state['_commission'] ?? $employee->default_commission_percentage ?? 0;
            $sync['active'] = isset($state['_active'])
                ? (bool) $state['_active']
                : (bool) $employee->active;
        }

        $employee->update($sync);
    }
}
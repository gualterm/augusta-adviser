<?php

namespace App\Filament\Resources\Appointments\Pages;

use App\Filament\Resources\Appointments\AppointmentResource;
use App\Models\Service;
use App\Services\AppointmentConflictService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;

class CreateAppointment extends CreateRecord
{
    protected static string $resource = AppointmentResource::class;
    protected function afterFill(): void
    {
        $time = request()->query('appointment_time');
        if ($time && strlen($time) === 5) { $time .= ':00'; }
        $overrides = array_filter([
            'client_id'        => request()->query('client_id'),
            'employee_id'      => request()->query('employee_id'),
            'workstation_id'   => request()->query('workstation_id'),
            'appointment_date' => request()->query('appointment_date'),
            'appointment_time' => $time,
        ], fn($v) => $v !== null && $v !== '');
        if (!empty($overrides)) {
            $this->form->fill(array_merge($this->form->getRawState(), $overrides));
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $force = (bool) ($data['force_booking'] ?? false);
        unset($data['force_booking']);

        if ($force) {
            if (AppointmentConflictService::workstationAtCapacity(
                $data['workstation_id'],
                $data['appointment_date'],
                $data['appointment_time'],
                $data['end_time']
            )) {
                Notification::make()
                    ->title('Não foi possível gravar')
                    ->body('Este posto já tem o máximo de 2 marcações nesta hora. Escolhe outro posto ou horário.')
                    ->danger()
                    ->persistent()
                    ->send();

                throw new Halt();
            }

            Notification::make()
                ->title('Marcação gravada à força, ignorando conflitos.')
                ->warning()
                ->send();

            return $data;
        }

        $service = Service::find($data['service_id']);
        if (! $service) {
            Notification::make()
                ->title('Não foi possível gravar')
                ->body('Serviço inválido.')
                ->danger()
                ->persistent()
                ->send();

            throw new Halt();
        }

        $equipmentIds = $service->equipment->pluck('id')->all();

        $employeeConflict = AppointmentConflictService::employeeHasConflict(
            $data['employee_id'],
            $data['appointment_date'],
            $data['appointment_time'],
            $data['end_time']
        );

        $workstationConflict = AppointmentConflictService::workstationHasConflict(
            $data['workstation_id'],
            $data['appointment_date'],
            $data['appointment_time'],
            $data['end_time']
        );

        $equipmentConflict = ! empty($equipmentIds) && AppointmentConflictService::equipmentHasConflict(
            $equipmentIds,
            $data['appointment_date'],
            $data['appointment_time'],
            $data['end_time']
        );

        if (! $employeeConflict && ! $workstationConflict && ! $equipmentConflict) {
            return $data;
        }

        $suggestion = AppointmentConflictService::suggestFixedDelaySlot(
            $data['employee_id'],
            $service->workstation_type,
            $data['appointment_date'],
            $data['appointment_time'],
            $service->duration_minutes,
            null,
            $equipmentIds
        );

        if (! $suggestion) {
            $reason = $equipmentConflict
                ? 'O equipamento necessário para este serviço já está todo em uso nesta hora (mesmo noutra sala).'
                : 'Continua a haver conflito mesmo 10 minutos depois.';

            Notification::make()
                ->title('Não foi possível gravar — conflito de agenda')
                ->body($reason . ' Ativa "Forçar Marcação" se quiseres gravar mesmo assim (máximo 2 marcações no mesmo posto/hora).')
                ->danger()
                ->persistent()
                ->send();

            throw new Halt();
        }

        Notification::make()
            ->title(
                'Conflito detetado — horário ajustado automaticamente (+10 min) para ' .
                $suggestion['start'] . ', posto ' . $suggestion['workstation_name']
            )
            ->warning()
            ->persistent()
            ->send();

        $data['appointment_time'] = $suggestion['start'];
        $data['end_time'] = $suggestion['end'];
        $data['workstation_id']
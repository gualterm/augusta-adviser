<?php
namespace App\Filament\Resources\Appointments\Pages;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Models\Service;
use App\Services\AppointmentConflictService;
use Carbon\Carbon;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
class EditAppointment extends EditRecord
{
    protected static string $resource = AppointmentResource::class;
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $force    = (bool) ($data['force_booking'] ?? false);
        unset($data['force_booking']);
        $ignoreId = $this->getRecord()->id;
        // Calcular end_time a partir da duração do serviço se estiver null
        if (empty($data['end_time']) && !empty($data['service_id'])) {
            $svc = Service::find($data['service_id']);
            if ($svc) {
                $data['end_time'] = Carbon::parse($data['appointment_time'])
                    ->addMinutes($svc->duration_minutes)
                    ->format('H:i:s');
            }
        }
        if ($force) {
            if (AppointmentConflictService::workstationAtCapacity(
                $data['workstation_id'],
                $data['appointment_date'],
                $data['appointment_time'],
                $data['end_time'] ?? null,
                $ignoreId
            )) {
                Notification::make()
                    ->title('Não foi possível gravar')
                    ->body('Este posto já tem o máximo de 2 marcações nesta hora. Escolhe outro posto ou horário.')
                    ->danger()->persistent()->send();
                throw new Halt();
            }
            Notification::make()->title('Marcação gravada à força, ignorando conflitos.')->warning()->send();
            return $data;
        }
        $service = Service::find($data['service_id']);
        if (! $service) {
            Notification::make()
                ->title('Não foi possível gravar')->body('Serviço inválido.')
                ->danger()->persistent()->send();
            throw new Halt();
        }
        $equipmentIds    = $service->equipment->pluck('id')->all();
        $employeeConflict = AppointmentConflictService::employeeHasConflict(
            $data['employee_id'] ?? null,
            $data['appointment_date'],
            $data['appointment_time'],
            $data['end_time'] ?? null,
            $ignoreId
        );
        $workstationConflict = AppointmentConflictService::workstationHasConflict(
            $data['workstation_id'] ?? null,
            $data['appointment_date'],
            $data['appointment_time'],
            $data['end_time'] ?? null,
            $ignoreId
        );
        $equipmentConflict = ! empty($equipmentIds) && AppointmentConflictService::equipmentHasConflict(
            $equipmentIds,
            $data['appointment_date'],
            $data['appointment_time'],
            $data['end_time'] ?? null,
            $ignoreId
        );
        if (! $employeeConflict && ! $workstationConflict && ! $equipmentConflict) {
            return $data;
        }
        $suggestion = AppointmentConflictService::suggestFixedDelaySlot(
            $data['employee_id'] ?? null,
            $service->workstation_type,
            $data['appointment_date'],
            $data['appointment_time'],
            $service->duration_minutes,
            $ignoreId,
            $equipmentIds
        );
        if (! $suggestion) {
            $reason = $equipmentConflict
                ? 'O equipamento necessário para este serviço já está todo em uso nesta hora (mesmo noutra sala).'
                : 'Continua a haver conflito mesmo 10 minutos depois.';
            Notification::make()
                ->title('Não foi possível gravar — conflito de agenda')
                ->body($reason . ' Ativa "Forçar Marcação" se quiseres gravar mesmo assim (máximo 2 marcações no mesmo posto/hora).')
                ->danger()->persistent()->send();
            throw new Halt();
        }
        Notification::make()
            ->title('Conflito detetado — horário ajustado automaticamente (+10 min) para ' . $suggestion['start'] . ', posto ' . $suggestion['workstation_name'])
            ->warning()->persistent()->send();
        $data['appointment_time'] = $suggestion['start'];
        $data['end_time']         = $suggestion['end'];
        $data['workstation_id']   = $suggestion['workstation_id'];
        return $data;
    }
}
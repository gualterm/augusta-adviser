<?php
namespace App\Filament\Resources\Appointments\Pages;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Models\Appointment;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
class ListAppointments extends ListRecords
{
    protected static string $resource = AppointmentResource::class;
    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportExcel')
                ->label('Exportar Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    return response()->streamDownload(function () {
                        $handle = fopen('php://output', 'w');
                        fwrite($handle, "\xEF\xBB\xBF");
                        fputcsv($handle, ['Data', 'Hora', 'Cliente', 'Profissional', '2º Terapeuta', 'Serviço', 'Posto', 'Estado', 'Origem', 'Preço (€)', 'Notas'], ';');
                        Appointment::with(['client', 'employee', 'secondaryEmployee', 'service', 'workstation'])
                            ->orderBy('appointment_date')
                            ->orderBy('appointment_time')
                            ->get()
                            ->each(function ($a) use ($handle) {
                                fputcsv($handle, [
                                    $a->appointment_date?->format('d/m/Y'),
                                    $a->appointment_time ? substr($a->appointment_time, 0, 5) : '',
                                    $a->client?->name,
                                    $a->employee?->name,
                                    $a->secondaryEmployee?->name,
                                    $a->service?->name,
                                    $a->workstation?->name,
                                    match ($a->status) { 'scheduled' => 'Agendada', 'confirmed' => 'Confirmada', 'completed' => 'Concluída', 'cancelled' => 'Cancelada', default => $a->status },
                                    $a->source ?: 'Direto',
                                    $a->price !== null ? number_format((float) $a->price, 2, '.', '') : '',
                                    $a->notes,
                                ], ';');
                            });
                        fclose($handle);
                    }, 'agenda_augusta_adviser_' . now()->format('Y-m-d') . '.csv');
                }),
            CreateAction::make()->visible(fn() => static::getResource()::canCreate()),
        ];
    }
}

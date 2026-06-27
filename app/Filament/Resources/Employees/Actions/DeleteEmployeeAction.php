<?php
namespace App\Filament\Resources\Employees\Actions;
use App\Models\Appointment;
use App\Models\Employee;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
class DeleteEmployeeAction {
    public static function make(): Action {
        return Action::make('delete')
            ->label('Eliminar')
            ->color('danger')
            ->icon('heroicon-o-trash')
            ->requiresConfirmation()
            ->modalHeading('Eliminar Profissional')
            ->modalDescription(function ($record): \Illuminate\Support\HtmlString {
                $count = Appointment::where('employee_id', $record->id)
                    ->whereIn('status', ['scheduled', 'confirmed'])
                    ->count();
                if ($count > 0) {
                    return new \Illuminate\Support\HtmlString(
                        "<div style='text-align:left'>"
                        . "<p style='margin-bottom:12px'><strong>" . e($record->name) . "</strong>"
                        . " tem <strong>{$count} marcação(ões) ativa(s)</strong>.</p>"
                        . "<div style='background:#fef3c7;border-left:4px solid #f59e0b;"
                        . "padding:12px 16px;border-radius:4px;color:#92400e'>"
                        . "<strong>⚠️ Não é possível eliminar sem transferir as marcações.</strong><br><br>"
                        . "Para proteger os clientes, um profissional só pode ser eliminado depois de "
                        . "todas as suas marcações ativas serem atribuídas a outro profissional. "
                        . "Escolhe abaixo quem deve receber estas marcações."
                        . "</div></div>"
                    );
                }
                return new \Illuminate\Support\HtmlString(
                    "<p>Tem a certeza que quer eliminar <strong>" . e($record->name) . "</strong>?<br>"
                    . "<span style='color:#6b7280;font-size:0.875rem'>Esta ação não pode ser revertida.</span></p>"
                );
            })
            ->form(function ($record): array {
                $count = Appointment::where('employee_id', $record->id)
                    ->whereIn('status', ['scheduled', 'confirmed'])
                    ->count();
                if ($count === 0) return [];
                return [
                    Select::make('transfer_to')
                        ->label("Transferir {$count} marcação(ões) para")
                        ->placeholder('Selecione um profissional...')
                        ->options(
                            Employee::where('id', '!=', $record->id)
                                ->where('active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->toArray()
                        )
                        ->required()
                        ->searchable(),
                ];
            })
            ->action(function ($record, array $data, $livewire): void {
                $count = Appointment::where('employee_id', $record->id)
                    ->whereIn('status', ['scheduled', 'confirmed'])
                    ->count();
                $name = $record->name;
                if ($count > 0 && !empty($data['transfer_to'])) {
                    $target = Employee::find($data['transfer_to']);
                    Appointment::where('employee_id', $record->id)
                        ->whereIn('status', ['scheduled', 'confirmed'])
                        ->update(['employee_id' => $data['transfer_to']]);
                    Notification::make()
                        ->title('Marcações transferidas')
                        ->body("{$count} marcação(ões) de {$name} transferida(s) para {$target?->name}.")
                        ->success()
                        ->send();
                }
                $record->delete();
                Notification::make()
                    ->title("{$name} eliminado(a) com sucesso")
                    ->success()
                    ->send();
                if ($livewire instanceof EditRecord) {
                    $livewire->redirect(
                        \App\Filament\Resources\Employees\EmployeeResource::getUrl('index')
                    );
                }
            });
    }
}
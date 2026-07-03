<?php

namespace App\Filament\Resources\ExternalBookings\Tables;

use App\Models\Employee;
use App\Models\Workstation;
use App\Services\ExternalBookingConfirmer;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class ExternalBookingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('appointment_date')
            ->recordClasses(fn (Model $record): string => match (true) {
                $record->has_conflict => '!bg-danger-50',
                $record->appointment_id !== null => '!bg-gray-100 opacity-70',
                default => '!bg-success-50',
            })
            ->columns([
                TextColumn::make('client_name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),
                // Estado "na agenda?" logo a seguir ao cliente, de propósito — é a
                // pergunta mais importante ("isto já foi integrado ou não?") e não
                // deve exigir scroll horizontal para se ver.
                TextColumn::make('appointment_id')
                    ->label('Na agenda?')
                    ->badge()
                    ->formatStateUsing(fn (?int $state): string => $state ? 'Já na agenda' : 'Por confirmar')
                    ->color(fn (?int $state): string => $state ? 'gray' : 'success'),
                TextColumn::make('conflict_note')
                    ->label('Conflito / aviso')
                    ->wrap()
                    ->color('danger')
                    ->placeholder('—'),
                TextColumn::make('appointment_date')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('appointment_time')
                    ->label('Hora')
                    ->time('H:i')
                    ->sortable(),
                TextColumn::make('external_status')
                    ->label('Estado Odisseias')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'CONFIRMADA' => 'success',
                        'REALIZADA' => 'info',
                        'ANULADA' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('price_net')
                    ->label('Preço NET')
                    ->money('EUR')
                    ->sortable()
                    ->summarize(Sum::make()->label('Total NET')->money('EUR')),
                TextColumn::make('channel')
                    ->label('Canal')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->toggleable(),
                TextColumn::make('reserva_number')
                    ->label('Nº Reserva')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),
                TextColumn::make('voucher_number')
                    ->label('Nº Voucher')
                    ->searchable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('channel')
                    ->label('Canal')
                    ->options(['odisseias' => 'Odisseias']),
                Filter::make('so_conflitos')
                    ->label('Mostrar só conflitos/erros')
                    ->query(fn (Builder $query): Builder => $query->where('has_conflict', true))
                    ->toggle(),
                Filter::make('por_confirmar')
                    ->label('Só por confirmar')
                    ->query(fn (Builder $query): Builder => $query->whereNull('appointment_id'))
                    ->toggle(),
            ])
            ->recordActions([
                Action::make('confirmar')
                    ->label('Confirmar')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (Model $record): bool => !$record->has_conflict && $record->appointment_id === null)
                    ->requiresConfirmation()
                    ->action(function (Model $record) {
                        static::confirmRecord($record);
                    }),
                Action::make('confirmar_com_conflito')
                    ->label('Confirmar mesmo assim')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('danger')
                    ->visible(fn (Model $record): bool => $record->has_conflict && $record->appointment_id === null)
                    ->requiresConfirmation()
                    ->modalDescription('Esta reserva tem um conflito de horário sinalizado. Só confirmes depois de resolver manualmente o horário/profissional na agenda.')
                    ->action(function (Model $record) {
                        static::confirmRecord($record);
                    }),
                Action::make('cancelar_marcacao')
                    ->label('Cancelar marcação')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Model $record): bool => $record->external_status === 'ANULADA'
                        && $record->appointment_id !== null
                        && $record->appointment?->status !== 'cancelled')
                    ->requiresConfirmation()
                    ->modalHeading('Cancelar marcação na agenda')
                    ->modalDescription(fn (Model $record): string => "Inconsistência detetada: a reserva {$record->reserva_number} ({$record->client_name}) foi anulada no portal da Odisseias, mas a marcação #{$record->appointment_id} "
                        . '(' . $record->appointment_date->format('d/m/Y') . ' ' . substr($record->appointment_time, 0, 5) . ') '
                        . 'continua agendada na agenda da Augusta. O lógico é cancelá-la também aqui — a marcação não é apagada, só passa a estado "Cancelada".')
                    ->modalSubmitActionLabel('Sim, cancelar na agenda')
                    ->action(function (Model $record) {
                        $record->appointment?->update(['status' => 'cancelled']);
                        $record->update([
                            'has_conflict' => false,
                            'conflict_note' => 'Anulada na Odisseias — marcação cancelada na agenda em ' . now()->format('d/m/Y H:i') . '.',
                        ]);

                        Notification::make()
                            ->title('Marcação cancelada na agenda da Augusta')
                            ->success()
                            ->persistent()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkAction::make('confirmar_selecionadas')
                    ->label('Confirmar selecionadas')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Collection $records) {
                        $confirmer = app(ExternalBookingConfirmer::class);
                        $employee = config('odisseias.default_employee_id') ? Employee::find(config('odisseias.default_employee_id')) : Employee::first();
                        $workstation = config('odisseias.default_workstation_id') ? Workstation::find(config('odisseias.default_workstation_id')) : Workstation::where('active', true)->first();

                        $confirmed = 0;
                        $skippedConflict = 0;
                        $errors = [];

                        foreach ($records as $record) {
                            if ($record->appointment_id !== null) {
                                continue;
                            }
                            if ($record->has_conflict) {
                                $skippedConflict++;
                                continue;
                            }
                            $result = $confirmer->confirm($record, $employee, $workstation);
                            if ($result['appointment']) {
                                $confirmed++;
                            } else {
                                $errors[] = "{$record->client_name}: {$result['error']}";
                            }
                        }

                        Notification::make()
                            ->title("{$confirmed} marcação(ões) confirmada(s) para a agenda")
                            ->body($skippedConflict ? "{$skippedConflict} ignorada(s) por terem conflito de horário — resolve à mão." : null)
                            ->warning($skippedConflict > 0 || count($errors) > 0)
                            ->success($skippedConflict === 0 && count($errors) === 0)
                            ->persistent()
                            ->send();

                        if ($errors) {
                            Notification::make()
                                ->title('Erros ao confirmar')
                                ->body(implode("\n", $errors))
                                ->danger()
                                ->persistent()
                                ->send();
                        }
                    }),
            ]);
    }

    private static function confirmRecord(Model $record): void
    {
        $confirmer = app(ExternalBookingConfirmer::class);
        $employee = config('odisseias.default_employee_id') ? Employee::find(config('odisseias.default_employee_id')) : Employee::first();
        $workstation = config('odisseias.default_workstation_id') ? Workstation::find(config('odisseias.default_workstation_id')) : Workstation::where('active', true)->first();

        $result = $confirmer->confirm($record, $employee, $workstation);

        if ($result['appointment']) {
            Notification::make()
                ->title('Marcação confirmada para a agenda')
                ->success()
                ->persistent()
                ->send();
        } else {
            Notification::make()
                ->title('Não foi possível confirmar')
                ->body($result['error'])
                ->danger()
                ->persistent()
                ->send();
        }
    }
}

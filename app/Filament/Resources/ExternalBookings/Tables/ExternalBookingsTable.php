<?php

namespace App\Filament\Resources\ExternalBookings\Tables;

use App\Filament\Resources\Appointments\AppointmentResource;
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
                $record->appointment_id !== null || $record->ignored_at !== null => '!bg-gray-100 opacity-70',
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
                    ->formatStateUsing(fn (?int $state, Model $record): string => match (true) {
                        $state !== null => 'Já na agenda',
                        $record->ignored_at !== null => 'Ignorada',
                        default => 'Por confirmar',
                    })
                    ->color(fn (?int $state, Model $record): string => match (true) {
                        $state !== null || $record->ignored_at !== null => 'gray',
                        default => 'success',
                    }),
                // Mensagem curta e direta na coluna, com o detalhe completo só ao
                // passar o rato por cima (tooltip) — pedido explícito para não
                // sobrecarregar a lista com texto longo por omissão.
                TextColumn::make('conflict_note')
                    ->label('Conflito / aviso')
                    ->formatStateUsing(fn (?string $state, Model $record): string => match (true) {
                        $state === null => '—',
                        str_contains($state, 'Choca com') => '⚠ Conflito de horário',
                        str_contains($state, 'Anulada na Odisseias') => '⚠ Anulada, já na agenda',
                        default => '⚠ Aviso',
                    })
                    ->tooltip(fn (?string $state): ?string => $state)
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
                // Ligado por omissão: uma vez confirmada, cancelada ou ignorada, a
                // reserva já foi tratada e não precisa de continuar a aparecer.
                // Complementa também as anuladas que NUNCA chegaram a entrar na
                // agenda (ex.: Tania Lopes) — não há nada para decidir nelas, por
                // isso escondem-se tal como as já tratadas; a exceção é uma
                // anulada que JÁ estava na agenda (has_conflict=true), essa
                // continua visível porque precisa da ação "Cancelar marcação".
                // Desliga este filtro para ver o histórico completo (30 reservas).
                Filter::make('por_tratar')
                    ->label('Esconder já tratadas / sem ação possível')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereNull('appointment_id')
                        ->whereNull('ignored_at')
                        ->where(fn (Builder $q) => $q->where('external_status', '!=', 'ANULADA')->orWhere('has_conflict', true)))
                    ->toggle()
                    ->default(true),
            ])
            ->recordActions([
                Action::make('confirmar')
                    ->label('Confirmar')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (Model $record): bool => !$record->has_conflict
                        && $record->appointment_id === null
                        && $record->ignored_at === null
                        && $record->external_status !== 'ANULADA')
                    ->requiresConfirmation()
                    ->action(function (Model $record) {
                        static::confirmRecord($record);
                    }),
                Action::make('ver_conflito')
                    ->label('Ver marcação em conflito')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->visible(fn (Model $record): bool => $record->has_conflict && $record->conflict_appointment_id !== null)
                    ->url(fn (Model $record): ?string => $record->conflict_appointment_id
                        ? AppointmentResource::getUrl('edit', ['record' => $record->conflict_appointment_id])
                        : null)
                    ->openUrlInNewTab(),
                Action::make('cancelar_existente_e_confirmar')
                    ->label('Cancelar existente e confirmar esta')
                    ->icon('heroicon-o-arrow-path-rounded-square')
                    ->color('danger')
                    ->visible(fn (Model $record): bool => $record->has_conflict && $record->appointment_id === null && $record->conflict_appointment_id !== null)
                    ->requiresConfirmation()
                    ->modalHeading('Cancelar marcação existente e confirmar esta reserva')
                    ->modalDescription(fn (Model $record): string => "Vou cancelar a marcação #{$record->conflict_appointment_id} (a que está a chocar) e confirmar em seu lugar a reserva da Odisseias de {$record->client_name}. A marcação antiga não é apagada, só fica com estado \"Cancelada\".")
                    ->modalSubmitActionLabel('Sim, cancelar e confirmar')
                    ->action(function (Model $record) {
                        $record->conflictAppointment?->update(['status' => 'cancelled']);
                        $record->update(['has_conflict' => false, 'conflict_note' => null, 'conflict_appointment_id' => null]);
                        static::confirmRecord($record);
                    }),
                Action::make('ignorar_reserva')
                    ->label('Ignorar esta reserva')
                    ->icon('heroicon-o-x-mark')
                    ->color('gray')
                    ->visible(fn (Model $record): bool => $record->has_conflict && $record->appointment_id === null)
                    ->requiresConfirmation()
                    ->modalDescription('Esta reserva não é confirmada para a agenda — mantém-se a marcação existente e esta linha deixa de aparecer na lista por omissão (podes sempre voltar a vê-la desligando o filtro "Esconder já tratadas").')
                    ->action(function (Model $record) {
                        $record->update([
                            'ignored_at' => now(),
                            'has_conflict' => false,
                            'conflict_note' => 'Ignorada manualmente em ' . now()->format('d/m/Y H:i') . ' — mantida a marcação existente.',
                        ]);

                        Notification::make()
                            ->title('Reserva ignorada')
                            ->success()
                            ->persistent()
                            ->send();
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
                            if ($record->appointment_id !== null || $record->ignored_at !== null || $record->external_status === 'ANULADA') {
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

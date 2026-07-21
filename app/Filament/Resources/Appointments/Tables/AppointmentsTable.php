<?php
namespace App\Filament\Resources\Appointments\Tables;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
class AppointmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('appointment_date')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('appointment_time')
                    ->label('Hora')
                    ->time('H:i')
                    ->sortable(),
                TextColumn::make('client.name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record) => $record->client_id
                        ? \App\Filament\Resources\Clients\ClientResource::getUrl('edit', ['record' => $record->client_id])
                        : null),
                TextColumn::make('notes')
                    ->label('Aviso')
                    ->formatStateUsing(fn (?string $state): ?string => match (true) {
                        self::isLunchRequestNote($state) => '⚠ Pedido de almoço',
                        filled($state) => '📝 Nota',
                        default => null,
                    })
                    ->badge()
                    ->color(fn (?string $state): string => self::isLunchRequestNote($state) ? 'warning' : 'gray')
                    ->tooltip(fn (?string $state): ?string => $state)
                    ->toggleable(),
                TextColumn::make('source')
                    ->label('Origem')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'Odisseias' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('employee.name')
                    ->label('Profissional')
                    ->searchable()
                    ->sortable()
                    ->placeholder('⚠ Sem profissional'),
                TextColumn::make('workstation.name')
                    ->label('Posto')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('service.name')
                    ->label('Serviço')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'scheduled' => 'Agendada',
                        'confirmed' => 'Confirmada',
                        'completed' => 'Concluída',
                        'cancelled' => 'Cancelada',
                        default => $state,
                    })
                    ->badge(),
                TextColumn::make('price')
                    ->label('Preço')
                    ->money('EUR'),
            ])
            ->filters([
                Filter::make('com_aviso')
                    ->label('⚠ Pedido de horário de almoço por confirmar')
                    ->query(fn (Builder $query): Builder => $query->where('notes', 'like', '%horário de almoço%'))
                    ->toggle(),
                Filter::make('sem_profissional')
                    ->label('⚠ Sem profissional')
                    ->query(fn (Builder $query): Builder => $query->whereDoesntHave('employee'))
                    ->toggle(),
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'scheduled' => 'Agendada',
                        'confirmed' => 'Confirmada',
                        'completed' => 'Concluída',
                        'cancelled' => 'Cancelada',
                    ]),
                SelectFilter::make('source')
                    ->label('Origem')
                    ->options([
                        'Direto' => 'Direto',
                        'Odisseias' => 'Odisseias',
                    ]),
            ])
            ->recordActions([
                Action::make('aceitar_aviso')
                    ->label('Aceitar')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (Model $record): bool => self::isLunchRequestNote($record->notes) && $record->status !== 'cancelled')
                    ->requiresConfirmation()
                    ->modalDescription('Confirmas esta marcação (pedido de horário de almoço)? O aviso desaparece da lista.')
                    ->action(function (Model $record) {
                        $record->update(['notes' => null]);
                        Notification::make()->success()->title('Marcação confirmada')->send();
                    }),
                Action::make('recusar_aviso')
                    ->label('Recusar')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (Model $record): bool => self::isLunchRequestNote($record->notes) && $record->status !== 'cancelled')
                    ->requiresConfirmation()
                    ->modalHeading('Recusar e cancelar esta marcação')
                    ->modalDescription('A marcação fica cancelada e o cliente recebe um email automático com o motivo que escreveres abaixo e um link para escolher outra hora.')
                    ->modalSubmitActionLabel('Recusar e enviar email')
                    ->form([
                        Textarea::make('motivo')
                            ->label('Motivo do cancelamento (vai no email ao cliente)')
                            ->placeholder('Ex.: Infelizmente não temos disponibilidade a essa hora de almoço.')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (Model $record, array $data) {
                        $reason = $data['motivo'];
                        $record->update([
                            'status' => 'cancelled',
                            'notes'  => 'Pedido recusado pela clínica: ' . $reason,
                        ]);

                        try {
                            $record->client?->notify(new \App\Notifications\AppointmentCancelledNotification($record, $reason));
                            Notification::make()->warning()->title('Marcação recusada e email enviado ao cliente')->send();
                        } catch (\Throwable $e) {
                            report($e);
                            Notification::make()->danger()->title('Marcação recusada, mas o email falhou')->body('Contacta o cliente diretamente.')->send();
                        }
                    }),
                EditAction::make()->visible(fn($record) => \App\Filament\Resources\Appointments\AppointmentResource::canEdit($record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->visible(fn() => \App\Filament\Resources\Appointments\AppointmentResource::canDeleteAny()),
                ]),
            ]);
    }

    /**
     * Distingue um pedido de horário de almoço (criado por
     * ClientPortalController::book/saveReschedule) de uma nota qualquer —
     * só o primeiro deve mostrar o aviso acionável (Aceitar/Recusar).
     */
    public static function isLunchRequestNote(?string $notes): bool
    {
        return $notes !== null && str_contains($notes, 'horário de almoço');
    }
}
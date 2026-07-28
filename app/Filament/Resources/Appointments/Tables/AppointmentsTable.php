<?php
namespace App\Filament\Resources\Appointments\Tables;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select as FormSelect;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
class AppointmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort(fn (Builder $query): Builder => $query->orderBy('appointment_date', 'asc')->orderBy('appointment_time', 'asc'))
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
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                Filter::make('periodo')
                    ->label('📅 Período')
                    ->form([
                        DatePicker::make('de')
                            ->label('De')
                            ->default(today())
                            ->displayFormat('d/m/Y'),
                        DatePicker::make('ate')
                            ->label('Até')
                            ->displayFormat('d/m/Y'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['de'] ?? null, fn ($q, $v) => $q->whereDate('appointment_date', '>=', $v))
                            ->when($data['ate'] ?? null, fn ($q, $v) => $q->whereDate('appointment_date', '<=', $v));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['de'] ?? null) $indicators[] = 'De: ' . \Carbon\Carbon::parse($data['de'])->format('d/m/Y');
                        if ($data['ate'] ?? null) $indicators[] = 'Até: ' . \Carbon\Carbon::parse($data['ate'])->format('d/m/Y');
                        return $indicators;
                    }),
                Filter::make('com_aviso')
                    ->label('⚠ Pedido de horário de almoço por confirmar')
                    ->query(fn (Builder $query): Builder => $query->where('notes', 'like', '%horário de almoço%'))
                    ->toggle(),
                Filter::make('sem_profissional')
                    ->label('⚠ Sem profissional')
                    ->query(fn (Builder $query): Builder => $query->whereDoesntHave('employee'))
                    ->toggle(),
                Filter::make('sem_preco')
                    ->label('⚠ Sem preço definido')
                    ->query(fn (Builder $query): Builder => $query->where(fn ($q) => $q->whereNull('price')->orWhere('price', '<=', 0)))
                    ->toggle(),
                Filter::make('passadas_agendadas')
                    ->label('⚠ Passadas ainda Agendadas')
                    ->query(fn (Builder $query): Builder => $query
                        ->where('appointment_date', '<', now()->toDateString())
                        ->where('status', 'scheduled'))
                    ->toggle(),
                Filter::make('sobrepostas')
                    ->label('⚠ Marcações sobrepostas')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereNotNull('employee_id')
                        ->whereNotIn('status', ['cancelled'])
                        ->whereExists(function ($sub) {
                            $sub->from('appointments as a2')
                                ->whereColumn('a2.employee_id', 'appointments.employee_id')
                                ->whereColumn('a2.appointment_date', 'appointments.appointment_date')
                                ->whereColumn('a2.appointment_time', 'appointments.appointment_time')
                                ->whereColumn('a2.id', '<>', 'appointments.id')
                                ->whereNotIn('a2.status', ['cancelled']);
                        }))
                    ->toggle(),
                Filter::make('com_erros')
                    ->label('🚨 Todas com erros')
                    ->query(fn (Builder $query): Builder => $query->where(function ($q) {
                        $q->whereDoesntHave('employee')
                          ->orWhereNull('price')
                          ->orWhere('price', '<=', 0)
                          ->orWhere(fn ($s) => $s
                              ->where('appointment_date', '<', now()->toDateString())
                              ->where('status', 'scheduled'))
                          ->orWhere(fn ($s) => $s
                              ->whereNotNull('employee_id')
                              ->whereNotIn('status', ['cancelled'])
                              ->whereExists(function ($sub) {
                                  $sub->from('appointments as a2')
                                      ->whereColumn('a2.employee_id', 'appointments.employee_id')
                                      ->whereColumn('a2.appointment_date', 'appointments.appointment_date')
                                      ->whereColumn('a2.appointment_time', 'appointments.appointment_time')
                                      ->whereColumn('a2.id', '<>', 'appointments.id')
                                      ->whereNotIn('a2.status', ['cancelled']);
                              }));
                    })->distinct())
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
                    BulkAction::make('alterar_estado')
                        ->label('Alterar Estado')
                        ->icon('heroicon-o-pencil-square')
                        ->color('warning')
                        ->form([
                            FormSelect::make('status')
                                ->label('Novo estado para as marcações seleccionadas')
                                ->options([
                                    'scheduled' => 'Agendada',
                                    'confirmed' => 'Confirmada',
                                    'completed' => 'Concluída',
                                    'cancelled' => 'Cancelada',
                                ])
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $count = $records->count();
                            $records->each->update(['status' => $data['status']]);
                            $label = match($data['status']) {
                                'scheduled' => 'Agendada',
                                'confirmed' => 'Confirmada',
                                'completed' => 'Concluída',
                                'cancelled' => 'Cancelada',
                                default     => $data['status'],
                            };
                            Notification::make()
                                ->success()
                                ->title($count . ' marcações actualizadas para "' . $label . '"')
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
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

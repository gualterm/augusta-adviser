<?php

namespace App\Filament\Resources\Clients\RelationManagers;

use App\Filament\Resources\Appointments\AppointmentResource;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Mostra, na ficha do cliente, todas as marcações já associadas a ele —
 * pedido do Gualter (2026-07-06): partir do cliente e ver logo se já está
 * associado a marcações e se estas já têm serviço/profissional atribuído,
 * sem ter de procurar na Agenda geral.
 */
class AppointmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'appointments';

    protected static ?string $title = 'Marcações';

    protected static ?string $modelLabel = 'marcação';

    protected static ?string $pluralModelLabel = 'marcações';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->defaultSort('appointment_date', 'desc')
            ->columns([
                TextColumn::make('appointment_date')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('appointment_time')
                    ->label('Hora')
                    ->time('H:i')
                    ->sortable(),
                // Pergunta central pedida: "já está atribuído um serviço?" —
                // por isso o serviço vem logo a seguir à data/hora, com aviso
                // visual claro (vermelho) quando ainda não foi atribuído.
                TextColumn::make('service.name')
                    ->label('Serviço')
                    ->placeholder('⚠ Sem serviço atribuído')
                    ->color(fn (?string $state): ?string => $state === null ? 'danger' : null)
                    ->searchable(),
                TextColumn::make('employee.name')
                    ->label('Profissional')
                    ->placeholder('⚠ Sem profissional'),
                TextColumn::make('workstation.name')
                    ->label('Posto')
                    ->placeholder('—'),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'scheduled' => 'Agendada',
                        'confirmed' => 'Confirmada',
                        'completed' => 'Concluída',
                        'cancelled' => 'Cancelada',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'cancelled' => 'danger',
                        'completed' => 'success',
                        'confirmed' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('source')
                    ->label('Origem')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ?? 'Direto')
                    ->color(fn (?string $state): string => $state === 'Odisseias' ? 'warning' : 'gray'),
                TextColumn::make('price')
                    ->label('Preço')
                    ->money('EUR'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'scheduled' => 'Agendada',
                        'confirmed' => 'Confirmada',
                        'completed' => 'Concluída',
                        'cancelled' => 'Cancelada',
                    ]),
            ])
            ->headerActions([
                Action::make('nova_marcacao')
                    ->label('Nova Marcação')
                    ->icon('heroicon-o-plus')
                    ->url(fn (): string => AppointmentResource::getUrl('create') . '?' . http_build_query([
                        'client_id' => $this->getOwnerRecord()->id,
                    ]))
                    ->openUrlInNewTab(),
            ])
            ->recordActions([
                Action::make('abrir')
                    ->label('Abrir')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Model $record): string => AppointmentResource::getUrl('edit', ['record' => $record->id]))
                    ->openUrlInNewTab(),
            ]);
    }
}

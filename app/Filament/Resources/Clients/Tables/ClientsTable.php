<?php
namespace App\Filament\Resources\Clients\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ClientsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('is_presencial')
                    ->label('')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? '⚠ Incompleto' : '')
                    ->color(fn ($state) => $state ? 'warning' : null),
                TextColumn::make('gender')
                    ->label('Género')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'feminino'  => '♀ Feminino',
                        'masculino' => '♂ Masculino',
                        default     => '—',
                    })
                    ->sortable(),
                TextColumn::make('phone')
                    ->label('Telefone')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                TextColumn::make('nif')
                    ->label('NIF')
                    ->searchable(),
                IconColumn::make('active')
                    ->label('Ativo')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Criado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('is_presencial', 'desc')
            ->recordClasses(fn ($record) => $record->is_presencial ? 'bg-amber-50' : null)
            ->filters([
                SelectFilter::make('gender')
                    ->label('Género')
                    ->options([
                        'feminino'  => 'Feminino',
                        'masculino' => 'Masculino',
                    ]),
            ])
            ->recordActions([
                EditAction::make()->visible(
                    fn ($record) => \App\Filament\Resources\Clients\ClientResource::canEdit($record)
                ),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->visible(
                        fn () => \App\Filament\Resources\Clients\ClientResource::canDeleteAny()
                    ),
                ]),
            ]);
    }
}
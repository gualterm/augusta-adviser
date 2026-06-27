<?php
namespace App\Filament\Resources\Employees\Tables;
use App\Filament\Resources\Employees\Actions\DeleteEmployeeAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
class EmployeesTable {
    public static function configure(Table $table): Table {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nome')->searchable()->sortable(),
                TextColumn::make('role')->label('Função')->searchable(),
                TextColumn::make('phone')->label('Telefone'),
                TextColumn::make('email')->label('Email'),
                IconColumn::make('active')->label('Ativo')->boolean(),
                TextColumn::make('created_at')->label('Criado')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([])
            ->recordActions([
                EditAction::make()->visible(fn($record) => \App\Filament\Resources\Employees\EmployeeResource::canEdit($record)),
                DeleteEmployeeAction::make()->visible(fn($record) => \App\Filament\Resources\Employees\EmployeeResource::canDelete($record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
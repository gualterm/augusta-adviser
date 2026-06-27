<?php

namespace App\Filament\Resources\Workstations;

use App\Filament\Resources\Workstations\Pages\CreateWorkstation;
use App\Filament\Resources\Workstations\Pages\EditWorkstation;
use App\Filament\Resources\Workstations\Pages\ListWorkstations;
use App\Filament\Resources\Workstations\Schemas\WorkstationForm;
use App\Filament\Resources\Workstations\Tables\WorkstationsTable;
use App\Models\Workstation;
use BackedEnum;
use Filament\Resources\Resource;
use App\Filament\Traits\HasRolePermissions;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WorkstationResource extends Resource
{
    use HasRolePermissions;
    protected static ?string $model = Workstation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Postos';

    protected static ?string $modelLabel = 'Posto';

    protected static ?string $pluralModelLabel = 'Postos';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return WorkstationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WorkstationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWorkstations::route('/'),
            'create' => CreateWorkstation::route('/create'),
            'edit' => EditWorkstation::route('/{record}/edit'),
        ];
    }
}

<?php
namespace App\Filament\Resources\BusinessHours;

use App\Filament\Resources\BusinessHours\Pages\EditBusinessHour;
use App\Filament\Resources\BusinessHours\Pages\ListBusinessHours;
use App\Filament\Resources\BusinessHours\Schemas\BusinessHoursForm;
use App\Models\BusinessHour;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class BusinessHoursResource extends Resource
{
    protected static ?string $model = BusinessHour::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;
    protected static ?string $navigationLabel  = 'Horário da Loja';
    protected static UnitEnum|string|null $navigationGroup  = 'Configurações';
    protected static ?int    $navigationSort   = 10;
    protected static ?string $modelLabel       = 'Horário';
    protected static ?string $pluralModelLabel = 'Horário da Loja';

    public static function canCreate(): bool                                    { return false; }
    public static function canDelete(\Illuminate\Database\Eloquent\Model $r): bool { return false; }
    public static function canDeleteAny(): bool                                 { return false; }

    public static function form(Schema $schema): Schema
    {
        return BusinessHoursForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('day_of_week')
                    ->label('Dia')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => BusinessHour::DAY_NAMES[$state] ?? $state),
                IconColumn::make('is_open')
                    ->label('Aberto')
                    ->boolean(),
                TextColumn::make('open_time')
                    ->label('Abertura')
                    ->formatStateUsing(fn ($state) => $state ? substr($state, 0, 5) : '—'),
                TextColumn::make('close_time')
                    ->label('Fecho')
                    ->formatStateUsing(fn ($state) => $state ? substr($state, 0, 5) : '—'),
                TextColumn::make('lunch_start')
                    ->label('Almoço início')
                    ->formatStateUsing(fn ($state) => $state ? substr($state, 0, 5) : '—'),
                TextColumn::make('lunch_end')
                    ->label('Almoço fim')
                    ->formatStateUsing(fn ($state) => $state ? substr($state, 0, 5) : '—'),
            ])
            ->defaultSort('day_of_week')
            ->recordActions([EditAction::make()])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBusinessHours::route('/'),
            'edit'  => EditBusinessHour::route('/{record}/edit'),
        ];
    }
}
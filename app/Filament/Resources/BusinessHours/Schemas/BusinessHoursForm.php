<?php
namespace App\Filament\Resources\BusinessHours\Schemas;

use App\Models\BusinessHour;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BusinessHoursForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(fn ($record) => BusinessHour::DAY_NAMES[$record?->day_of_week ?? 0] ?? 'Horário')
                ->schema([
                    Toggle::make('is_open')
                        ->label('Loja aberta neste dia')
                        ->live(),
                    TimePicker::make('open_time')
                        ->label('Hora de abertura')
                        ->seconds(false)
                        ->visible(fn ($get) => (bool) $get('is_open')),
                    TimePicker::make('close_time')
                        ->label('Hora de fecho')
                        ->seconds(false)
                        ->visible(fn ($get) => (bool) $get('is_open')),
                ])
                ->columns(3),
        ]);
    }
}
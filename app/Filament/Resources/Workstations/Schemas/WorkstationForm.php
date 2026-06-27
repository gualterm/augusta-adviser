<?php

namespace App\Filament\Resources\Workstations\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WorkstationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dados do Posto')
                    ->schema([

                        TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255),

                        Select::make('type')
                            ->label('Tipo de Posto')
                            ->options([
                                'manicure' => 'Manicure',
                                'marquesa' => 'Sala de Tratamentos',
                                'geral' => 'Geral',
                            ])
                            ->required()
                            ->default('geral'),

                        Toggle::make('active')
                            ->label('Ativo')
                            ->default(true),

                        Textarea::make('description')
                            ->label('Descrição')
                            ->rows(4)
                            ->columnSpanFull(),

                    ])
                    ->columns(2),
            ]);
    }
}

<?php

namespace App\Filament\Resources\Services\Schemas;

use App\Models\Area;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ServiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dados do Serviço')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('category')
                            ->label('Categoria')
                            ->required(),
                        Select::make('workstation_type')
                            ->label('Tipo de Posto Necessário')
                            ->options([
                                'manicure' => 'Manicure',
                                'marquesa' => 'Sala de Tratamentos',
                                'geral' => 'Geral',
                            ])
                            ->required(),
                        Select::make('equipment')
                            ->label('Equipamentos Necessários')
                            ->relationship('equipment', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()->preload()
                            ->helperText('Seleciona equipamentos partilhados que este serviço usa (ex: Laser, Secador UV). Se um equipamento tiver só 1 unidade, não pode haver 2 marcações desse equipamento ao mesmo tempo, mesmo em salas/postos diferentes.'),
                        Select::make('areas')
                            ->label('Áreas que executam este serviço')
                            ->relationship('areas', 'name')
                            ->multiple()->preload()
                            ->helperText('Define quais áreas podem realizar este serviço'),
                        TextInput::make('price')
                            ->label('Preço (€)')
                            ->numeric()
                            ->required(),
                        TextInput::make('duration_minutes')
                            ->label('Duração (minutos)')
                            ->numeric()
                            ->default(60)
                            ->required(),
                        Textarea::make('description')
                            ->label('Descrição')
                            ->rows(4)
                            ->columnSpanFull(),
                        Toggle::make('active')
                            ->label('Ativo')
                            ->default(true),
                        Toggle::make('two_employees')
                            ->label('Requer 2 Terapeutas (ex: Massagem a 4 Maos)')
                            ->default(false),
                    ])
                    ->columns(2),
            ]);
    }
}

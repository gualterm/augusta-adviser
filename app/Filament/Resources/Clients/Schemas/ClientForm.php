<?php
namespace App\Filament\Resources\Clients\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ClientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dados do Cliente')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255),
                        Select::make('gender')
                            ->label('Género')
                            ->options([
                                'feminino'  => 'Feminino',
                                'masculino' => 'Masculino',
                            ])
                            ->placeholder('Não especificado'),
                        TextInput::make('phone')
                            ->label('Telefone')
                            ->tel()
                            ->maxLength(50),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255),
                        DatePicker::make('birth_date')
                            ->label('Data de Nascimento'),
                        TextInput::make('nif')
                            ->label('NIF')
                            ->maxLength(20),
                        TextInput::make('address')
                            ->label('Morada')
                            ->maxLength(255),
                        Textarea::make('notes')
                            ->label('Notas')
                            ->rows(4),
                        Toggle::make('active')
                            ->label('Ativo')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }
}
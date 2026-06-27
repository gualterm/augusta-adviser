<?php

namespace App\Filament\Resources\Inquiries\Schemas;

use App\Models\Inquiry;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InquiryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dados do Inquérito')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome')
                            ->disabled(),
                        TextInput::make('email')
                            ->label('Email')
                            ->disabled(),
                        TextInput::make('phone')
                            ->label('Telefone')
                            ->disabled(),
                        Select::make('subject')
                            ->label('Assunto')
                            ->options(Inquiry::SUBJECTS)
                            ->disabled(),
                        Textarea::make('message')
                            ->label('Mensagem')
                            ->rows(5)
                            ->disabled()
                            ->columnSpanFull(),
                        Select::make('status')
                            ->label('Estado')
                            ->options(Inquiry::STATUSES)
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }
}

<?php

namespace App\Filament\Resources\Inquiries\Schemas;

use App\Models\Inquiry;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\DateTimePicker;
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
                        Textarea::make('response')
                            ->label('Resposta enviada')
                            ->rows(6)
                            ->disabled()
                            ->columnSpanFull()
                            ->visible(fn ($record) => $record?->responded_at !== null),
                        DateTimePicker::make('responded_at')
                            ->label('Respondido em')
                            ->disabled()
                            ->displayFormat('d/m/Y H:i')
                            ->visible(fn ($record) => $record?->responded_at !== null),
                    ])
                    ->columns(2),
            ]);
    }
}

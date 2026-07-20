<?php
namespace App\Filament\Resources\Clients\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Section;
use Illuminate\Support\HtmlString;
use Filament\Schemas\Schema;

class ClientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Placeholder::make('aviso_presencial')
                    ->label('')
                    ->content(fn ($record) => $record?->is_presencial
                        ? new HtmlString('<div style="background:#fef3c7;border-left:4px solid #f59e0b;border-radius:6px;padding:12px 16px;color:#92400e;font-size:14px;font-weight:600;margin-bottom:4px;">⚠️ Ficha incompleta — cliente criado presencialmente. Preencha o email ou telefone para remover este aviso.</div>')
                        : new HtmlString(''))
                    ->visible(fn ($record) => $record?->is_presencial === true),
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

                Section::make('RGPD / Consentimento')
                    ->schema([
                        Placeholder::make('consent_status')
                            ->label('Consentimento RGPD')
                            ->content(fn ($record) => $record?->data_consent_at
                                ? new HtmlString('<span style="color:#16a34a;font-weight:600;font-size:14px;">&#10003; Formulário assinado em ' . \Carbon\Carbon::parse($record->consented_at)->format('d/m/Y H:i') . '</span>')
                                : new HtmlString('<span style="color:#dc2626;font-weight:600;font-size:14px;">&#10007; Ainda não assinou — entregar formulário</span>')),
                        Placeholder::make('marketing_status')
                            ->label('Aceita Marketing')
                            ->content(fn ($record) => (bool)($record?->marketing_consent)
                                ? new HtmlString('<span style="color:#16a34a;font-size:14px;">&#10003; Sim</span>')
                                : new HtmlString('<span style="color:#6b7280;font-size:14px;">&#10007; Não</span>')),
                    ])
                    ->columns(2),
            ]);
    }
}
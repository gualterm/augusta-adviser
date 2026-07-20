<?php
namespace App\Filament\Resources\ClientConsents;

use App\Filament\Resources\ClientConsents\Pages\ListClientConsents;
use App\Models\ClientConsent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Vista de leitura dos consentimentos RGPD obtidos — formulário presencial ou portal.
 */
class ClientConsentResource extends Resource
{
    protected static ?string $model = ClientConsent::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;
    protected static ?string $navigationLabel = 'Consentimentos';
    protected static ?string $modelLabel = 'Consentimento';
    protected static ?string $pluralModelLabel = 'Consentimentos RGPD';
    protected static string|UnitEnum|null $navigationGroup = 'Configurações';
    protected static ?int $navigationSort = 98;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('Ref.')
                    ->prefix('#')
                    ->sortable()
                    ->width(60),
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('phone')
                    ->label('Telemóvel')
                    ->placeholder('—'),
                TextColumn::make('client.name')
                    ->label('Cliente Augusta')
                    ->placeholder('Não associado')
                    ->default('—'),
                IconColumn::make('marketing_consent')
                    ->label('Marketing')
                    ->boolean()
                    ->trueIcon(Heroicon::OutlinedCheck)
                    ->falseIcon(Heroicon::OutlinedXMark)
                    ->trueColor('success')
                    ->falseColor('gray'),
                IconColumn::make('signature_data')
                    ->label('Assinatura')
                    ->boolean()
                    ->getStateUsing(fn ($record) => !empty($record->signature_data))
                    ->trueIcon(Heroicon::OutlinedPencil)
                    ->falseIcon(Heroicon::OutlinedXMark)
                    ->trueColor('info')
                    ->falseColor('gray'),
                TextColumn::make('consented_at')
                    ->label('Data de consentimento')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('consented_at', 'desc')
            ->recordActions([
                DeleteAction::make(),
            ])
            ->emptyStateHeading('Sem consentimentos registados')
            ->emptyStateDescription('Os consentimentos aparecem aqui quando o cliente preenche o formulário em /consentimento/ ou quando o admin regista manualmente.');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClientConsents::route('/'),
        ];
    }
}
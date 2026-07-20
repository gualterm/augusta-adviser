<?php
namespace App\Filament\Resources\OdisseiasServiceMappings;

use App\Filament\Resources\OdisseiasServiceMappings\Pages\CreateOdisseiasServiceMapping;
use App\Filament\Resources\OdisseiasServiceMappings\Pages\EditOdisseiasServiceMapping;
use App\Filament\Resources\OdisseiasServiceMappings\Pages\ListOdisseiasServiceMappings;
use App\Models\OdisseiasServiceMapping;
use App\Models\Service;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Mapeamentos dinâmicos de produtos Odisseias → serviços internos da Augusta.
 * Quando a Odisseias usa um nome de produto diferente do nome de serviço
 * registado na Augusta, a Marta define aqui a correspondência — sem código,
 * sem deploy.
 */
class OdisseiasServiceMappingResource extends Resource
{
    protected static ?string $model = OdisseiasServiceMapping::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;
    protected static ?string $navigationLabel = 'Mapeamentos Odisseias';
    protected static ?string $modelLabel = 'Mapeamento';
    protected static ?string $pluralModelLabel = 'Mapeamentos Odisseias';
    protected static string|UnitEnum|null $navigationGroup = 'Configurações';
    protected static ?int $navigationSort = 99;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('odisseias_name')
                ->label('Nome do produto na Odisseias')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255)
                ->helperText('Exactamente como aparece no feed da Odisseias — cuidado com maiúsculas/minúsculas.'),

            Select::make('service_id')
                ->label('Serviço Augusta')
                ->required()
                ->options(Service::orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->helperText('O serviço interno que corresponde a este produto.'),

            TextInput::make('notes')
                ->label('Nota (opcional)')
                ->maxLength(255)
                ->placeholder('Ex: produto genérico — radiofrequência + drenagem 90 min'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('odisseias_name')
                    ->label('Produto Odisseias')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('service.name')
                    ->label('Serviço Augusta')
                    ->sortable(),
                TextColumn::make('notes')
                    ->label('Nota')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->label('Atualizado')
                    ->since()
                    ->toggleable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->emptyStateHeading('Sem mapeamentos')
            ->emptyStateDescription('Quando surgir um produto Odisseias sem correspondência, usa a acção "Mapear e Confirmar" na lista de Marcações Externas, ou adiciona aqui directamente.');
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListOdisseiasServiceMappings::route('/'),
            'create' => CreateOdisseiasServiceMapping::route('/create'),
            'edit'   => EditOdisseiasServiceMapping::route('/{record}/edit'),
        ];
    }
}
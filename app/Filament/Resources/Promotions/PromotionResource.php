<?php

namespace App\Filament\Resources\Promotions;

use App\Models\Promotion;
use App\Models\Service;
use BackedEnum;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use App\Filament\Traits\HasRolePermissions;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PromotionResource extends Resource
{
    protected static string|\UnitEnum|null $navigationGroup = 'Operações';
    protected static ?int $navigationSort = 10;

    use HasRolePermissions;
    protected static ?string $model = Promotion::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;
    protected static ?string $navigationLabel = 'Promoções';
    protected static ?string $modelLabel = 'Promoção';
    protected static ?string $pluralModelLabel = 'Promoções';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')
                ->label('Título da Promoção')
                ->placeholder('Ex: Terça Especial — Manicure')
                ->required()
                ->maxLength(150)
                ->columnSpanFull(),

            Select::make('type')
                ->label('Tipo')
                ->options([
                    'daily'   => '📅 Diária (amanhã)',
                    'weekly'  => '📆 Semanal (próxima semana)',
                    'special' => '🎉 Especial (Natal, Páscoa, Noivos…)',
                ])
                ->required()
                ->live()
                ->afterStateUpdated(function ($state, callable $set) {
                    if ($state === 'daily') {
                        $set('valid_from', Carbon::tomorrow()->format('Y-m-d'));
                        $set('valid_to',   Carbon::tomorrow()->format('Y-m-d'));
                    } elseif ($state === 'weekly') {
                        $set('valid_from', Carbon::now()->next('Monday')->format('Y-m-d'));
                        $set('valid_to',   Carbon::now()->next('Monday')->addDays(6)->format('Y-m-d'));
                    }
                }),

            Select::make('service_id')
                ->label('Serviço')
                ->placeholder('Todos os serviços')
                ->options(Service::where('active', true)->pluck('name', 'id'))
                ->searchable()
                ->nullable(),

            TextInput::make('discount_percentage')
                ->label('Desconto (%)')
                ->numeric()
                ->minValue(1)
                ->maxValue(100)
                ->suffix('%')
                ->required()
                ->live(onBlur: true),

            DatePicker::make('valid_from')
                ->label('De')
                ->required(),

            DatePicker::make('valid_to')
                ->label('Até')
                ->required(),


            CheckboxList::make('excluded_service_ids')
                ->label('Excluir serviços desta promoção')
                ->helperText('Serviços assinalados NÃO terão desconto, mesmo que a promoção seja "Todos os serviços".')
                ->options(function (callable $get) {
                    $pct = (float) ($get('discount_percentage') ?? 0);
                    return \App\Models\Service::where('active', true)
                        ->orderBy('category')
                        ->orderBy('name')
                        ->get()
                        ->mapWithKeys(function ($s) use ($pct) {
                            $label = $s->category . ' — ' . $s->name;
                            if ($pct > 0 && $s->price > 0) {
                                $orig = number_format((float) $s->price, 2, ',', '.');
                                $disc = number_format(round((float) $s->price * (1 - $pct / 100), 2), 2, ',', '.');
                                $label .= '  ·  €' . $orig . ' → €' . $disc;
                            }
                            return [$s->id => $label];
                        })
                        ->toArray();
                })
                ->visible(fn (callable $get) => $get('service_id') === null || $get('service_id') === '')
                ->columns(3)
                ->gridDirection('row')
                ->columnSpanFull(),
            Repeater::make('serviceDiscounts')
                ->label('Descontos específicos por serviço')
                ->helperText('Serviços aqui listados terão uma percentagem diferente do desconto global. Os restantes (não excluídos) usam o desconto global.')
                ->relationship('serviceDiscounts')
                ->schema([
                    Select::make('service_id')
                        ->label('Serviço')
                        ->options(
                            \App\Models\Service::where('active', true)
                                ->orderBy('category')
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(fn ($s) => [$s->id => $s->category . ' — ' . $s->name])
                                ->toArray()
                        )
                        ->searchable()
                        ->required()
                        ->live(),
                    TextInput::make('discount_percent')
                        ->label('Desconto (%)')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(100)
                        ->suffix('%')
                        ->required()
                        ->live(),
                    Placeholder::make('preco_preview')
                        ->label('Previsão de preço')
                        ->content(function (callable $get): string {
                            $serviceId = $get('service_id');
                            $pct = (float) $get('discount_percent');
                            if (!$serviceId || !$pct) return '—';
                            $service = \App\Models\Service::find($serviceId);
                            if (!$service || !$service->price) return '—';
                            $original   = (float) $service->price;
                            $discounted = round($original * (1 - $pct / 100), 2);
                            return '€ ' . number_format($original, 2, ',', '.')
                                 . ' → € ' . number_format($discounted, 2, ',', '.')
                                 . ' (−' . $pct . '%)';
                        }),
                ])
                ->columns(3)
                ->addActionLabel('+ Adicionar desconto específico')
                ->visible(fn (callable $get) => !$get('service_id'))
                ->columnSpanFull(),
            Toggle::make('active')
                ->label('Ativa')
                ->default(true)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Promoção')
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('type')
                    ->label('Tipo')
                    ->formatStateUsing(fn ($state) => $state === 'daily' ? '📅 Diária' : '📆 Semanal')
                    ->badge()
                    ->color(fn ($state) => $state === 'daily' ? 'warning' : 'info'),

                TextColumn::make('service.name')
                    ->label('Serviço')
                    ->default('Todos'),

                TextColumn::make('discount_percentage')
                    ->label('Desconto')
                    ->formatStateUsing(fn ($state) => "{$state}%")
                    ->badge()
                    ->color('success'),

                TextColumn::make('valid_from')
                    ->label('De')
                    ->date('d/m/Y'),

                TextColumn::make('valid_to')
                    ->label('Até')
                    ->date('d/m/Y'),

                IconColumn::make('active')
                    ->label('Ativa')
                    ->boolean(),
            ])
            ->defaultSort('valid_from', 'desc')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPromotions::route('/'),
            'create' => Pages\CreatePromotion::route('/create'),
            'edit'   => Pages\EditPromotion::route('/{record}/edit'),
        ];
    }
}

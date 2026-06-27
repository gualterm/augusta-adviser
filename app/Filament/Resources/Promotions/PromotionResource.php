<?php

namespace App\Filament\Resources\Promotions;

use App\Models\Promotion;
use App\Models\Service;
use BackedEnum;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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
                    'daily'  => '📅 Diária (amanhã)',
                    'weekly' => '📆 Semanal (próxima semana)',
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
                ->required(),

            DatePicker::make('valid_from')
                ->label('De')
                ->required(),

            DatePicker::make('valid_to')
                ->label('Até')
                ->required(),

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

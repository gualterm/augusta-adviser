<?php

namespace App\Filament\Pages;

use App\Models\Promotion;
use App\Models\Service;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class PricingOverview extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.pricing-overview';

    protected static ?\Illuminate\Support\Collection $promoCache = null;

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-currency-euro';
    }

    public static function getNavigationLabel(): string
    {
        return 'Preços & Promoções';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Operações';
    }

    public static function getNavigationSort(): ?int
    {
        return 9;
    }

    public function getTitle(): string
    {
        return 'Preços & Promoções';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Service::query())
            ->defaultSort('category')
            ->columns([
                TextColumn::make('category')
                    ->label('Categoria')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Serviço')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('price')
                    ->label('Preço')
                    ->money('EUR')
                    ->sortable(),
                TextColumn::make('promo_discount')
                    ->label('Desc.')
                    ->state(function (Service $record) {
                        $promo = static::bestPromotion($record);
                        $pct = $promo?->getEffectiveDiscount($record->id);
                        return $pct !== null ? number_format($pct, 0) . '%' : '—';
                    }),
                TextColumn::make('promo_price')
                    ->label('Preço Promo')
                    ->state(function (Service $record) {
                        $promo = static::bestPromotion($record);
                        $pct = $promo?->getEffectiveDiscount($record->id);
                        if ($pct === null) {
                            return '—';
                        }
                        $preco = round(((float) $record->price) * (1 - $pct / 100), 2);
                        return '€ ' . number_format($preco, 2, ',', '.');
                    })
                    ->weight(fn (Service $record) => static::bestPromotion($record) ? 'bold' : null)
                    ->color(fn (Service $record) => static::bestPromotion($record) ? 'success' : null),
                TextColumn::make('promo_title')
                    ->label('Promoção')
                    ->state(fn (Service $record) => static::bestPromotion($record)?->title ?? '—')
                    ->limit(15)
                    ->tooltip(fn (Service $record) => static::bestPromotion($record)?->title)
                    ->badge()
                    ->color(fn (Service $record) => static::bestPromotion($record) ? 'success' : 'gray'),
            ])
            ->recordActions([
                Action::make('editarPreco')
                    ->iconButton()
                    ->icon('heroicon-o-pencil-square')
                    ->color('gray')
                    ->tooltip('Editar Preço')
                    ->form([
                        TextInput::make('price')
                            ->label('Novo Preço (€)')
                            ->numeric()
                            ->required()
                            ->minValue(0),
                    ])
                    ->fillForm(fn (Service $record) => ['price' => $record->price])
                    ->action(function (Service $record, array $data) {
                        $record->update(['price' => $data['price']]);
                        Notification::make()
                            ->title('Preço atualizado')
                            ->success()
                            ->send();
                    }),
                Action::make('removerPromocao')
                    ->iconButton()
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->tooltip('Remover da Promoção')
                    ->visible(fn (Service $record) => (bool) static::bestPromotion($record))
                    ->requiresConfirmation()
                    ->modalDescription('Este serviço deixa de ter desconto nesta promoção. As restantes condições da promoção mantêm-se para os outros serviços.')
                    ->action(function (Service $record) {
                        $promo = static::bestPromotion($record);
                        if (! $promo) {
                            return;
                        }
                        if ($promo->service_id === null) {
                            $excluded = $promo->excluded_service_ids ?? [];
                            $excluded[] = $record->id;
                            $promo->update(['excluded_service_ids' => array_values(array_unique($excluded))]);
                        } else {
                            $promo->update(['active' => false]);
                        }
                        Notification::make()
                            ->title('Serviço removido da promoção')
                            ->success()
                            ->send();
                    }),
                Action::make('criarPromocao')
                    ->iconButton()
                    ->icon('heroicon-o-tag')
                    ->color('warning')
                    ->tooltip('Criar Promoção')
                    ->visible(fn (Service $record) => ! static::bestPromotion($record))
                    ->url(fn (Service $record) => \App\Filament\Resources\Promotions\PromotionResource::getUrl('create') . '?service_id=' . $record->id),
            ]);
    }

    protected static function activePromotions(): \Illuminate\Support\Collection
    {
        if (static::$promoCache === null) {
            $today = now()->format('Y-m-d');
            static::$promoCache = Promotion::where('active', true)
                ->where('valid_from', '<=', $today)
                ->where('valid_to', '>=', $today)
                ->with('serviceDiscounts')
                ->get();
        }
        return static::$promoCache;
    }

    protected static function bestPromotion(Service $service): ?Promotion
    {
        $best = null;
        $bestPct = null;

        foreach (static::activePromotions() as $promo) {
            $pct = $promo->getEffectiveDiscount($service->id);
            if ($pct !== null && ($bestPct === null || $pct > $bestPct)) {
                $bestPct = $pct;
                $best = $promo;
            }
        }

        return $best;
    }
}

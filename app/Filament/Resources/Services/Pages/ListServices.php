<?php

namespace App\Filament\Resources\Services\Pages;

use App\Filament\Resources\Services\ServiceResource;
use App\Models\Promotion;
use App\Models\Service;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListServices extends ListRecords
{
    protected static string $resource = ServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportExcel')
                ->label('Exportar Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    return response()->streamDownload(function () {
                        $today = now()->format('Y-m-d');

                        $promotions = Promotion::where('active', true)
                            ->where('valid_from', '<=', $today)
                            ->where('valid_to', '>=', $today)
                            ->with('serviceDiscounts')
                            ->get();

                        $handle = fopen('php://output', 'w');
                        fwrite($handle, "\xEF\xBB\xBF");
                        fputcsv($handle, [
                            'Categoria',
                            'Nome',
                            'Descrição',
                            'Descritivo de Marketing',
                            'Preço (€)',
                            'Duração (min)',
                            'Em Promoção',
                            'Promoção',
                            'Desconto (%)',
                            'Preço com Promoção (€)',
                        ], ';');

                        Service::orderBy('category')->orderBy('name')->get()->each(function ($service) use ($handle, $promotions) {
                            $best = null;
                            $bestPct = null;

                            foreach ($promotions as $promo) {
                                $pct = $promo->getEffectiveDiscount($service->id);

                                if ($pct !== null && ($bestPct === null || $pct > $bestPct)) {
                                    $bestPct = $pct;
                                    $best = $promo;
                                }
                            }

                            $precoComPromo = $bestPct !== null
                                ? round(((float) $service->price) * (1 - $bestPct / 100), 2)
                                : null;

                            fputcsv($handle, [
                                $service->category,
                                $service->name,
                                $service->description,
                                $service->marketing_description,
                                number_format((float) $service->price, 2, '.', ''),
                                $service->duration_minutes,
                                $bestPct !== null ? 'Sim' : 'Não',
                                $best->title ?? '',
                                $bestPct !== null ? number_format($bestPct, 0, '.', '') . '%' : '',
                                $precoComPromo !== null ? number_format($precoComPromo, 2, '.', '') : '',
                            ], ';');
                        });

                        fclose($handle);
                    }, 'servicos_augusta_adviser_' . now()->format('Y-m-d') . '.csv');
                }),
            CreateAction::make()->visible(fn() => static::getResource()::canCreate()),
        ];
    }
}

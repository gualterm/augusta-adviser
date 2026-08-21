<?php
namespace App\Filament\Resources\Promotions\Pages;
use App\Filament\Resources\Promotions\PromotionResource;
use App\Models\Promotion;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
class ListPromotions extends ListRecords
{
    protected static string $resource = PromotionResource::class;
    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportExcel')
                ->label('Exportar Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    return response()->streamDownload(function () {
                        $handle = fopen('php://output', 'w');
                        fwrite($handle, "\xEF\xBB\xBF");
                        fputcsv($handle, ['Título', 'Tipo', 'Serviço', 'Desconto (%)', 'Válido de', 'Válido até', 'Ativa'], ';');
                        Promotion::with('service')->orderByDesc('valid_from')->get()->each(function ($promo) use ($handle) {
                            fputcsv($handle, [
                                $promo->title,
                                match ($promo->type) { 'daily' => 'Diária', 'weekly' => 'Semanal', 'special' => 'Especial', default => $promo->type },
                                $promo->service?->name ?? 'Todos os serviços',
                                number_format((float) $promo->discount_percentage, 0, ',', '') . '%',
                                $promo->valid_from?->format('d/m/Y'),
                                $promo->valid_to?->format('d/m/Y'),
                                $promo->active ? 'Sim' : 'Não',
                            ], ';');
                        });
                        fclose($handle);
                    }, 'promocoes_augusta_adviser_' . now()->format('Y-m-d') . '.csv');
                }),
            CreateAction::make()->visible(fn() => static::getResource()::canCreate()),
        ];
    }
}

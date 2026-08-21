<?php
namespace App\Filament\Resources\Equipment\Pages;
use App\Filament\Resources\Equipment\EquipmentResource;
use App\Models\Equipment;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
class ListEquipment extends ListRecords
{
    protected static string $resource = EquipmentResource::class;
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
                        fputcsv($handle, ['Nome', 'Quantidade', 'Ativo', 'Criado em'], ';');
                        Equipment::orderBy('name')->get()->each(function ($equipment) use ($handle) {
                            fputcsv($handle, [
                                $equipment->name,
                                $equipment->quantity,
                                $equipment->active ? 'Sim' : 'Não',
                                $equipment->created_at?->format('d/m/Y H:i'),
                            ], ';');
                        });
                        fclose($handle);
                    }, 'equipamentos_augusta_adviser_' . now()->format('Y-m-d') . '.csv');
                }),
            CreateAction::make()->visible(fn() => static::getResource()::canCreate()),
        ];
    }
}

<?php
namespace App\Filament\Resources\Equipment\Pages;
use App\Filament\Resources\Equipment\EquipmentResource;
use App\Models\Equipment;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
class EditEquipment extends EditRecord
{
    protected static string $resource = EquipmentResource::class;
    protected function getHeaderActions(): array
    {
        $currentId = $this->getRecord()->id;
        $previousId = Equipment::where('id', '<', $currentId)->orderByDesc('id')->value('id');
        $nextId = Equipment::where('id', '>', $currentId)->orderBy('id')->value('id');
        return [
            Action::make('previousEquipment')
                ->label('◀ Anterior')
                ->color('gray')
                ->visible((bool) $previousId)
                ->url($previousId ? EquipmentResource::getUrl('edit', ['record' => $previousId]) : null),
            Action::make('nextEquipment')
                ->label('Seguinte ▶')
                ->color('gray')
                ->visible((bool) $nextId)
                ->url($nextId ? EquipmentResource::getUrl('edit', ['record' => $nextId]) : null),
            DeleteAction::make(),
        ];
    }
}

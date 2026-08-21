<?php
namespace App\Filament\Resources\Promotions\Pages;
use App\Filament\Resources\Promotions\PromotionResource;
use App\Models\Promotion;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
class EditPromotion extends EditRecord
{
    protected static string $resource = PromotionResource::class;
    protected function getHeaderActions(): array
    {
        $currentId = $this->getRecord()->id;
        $previousId = Promotion::where('id', '<', $currentId)->orderByDesc('id')->value('id');
        $nextId = Promotion::where('id', '>', $currentId)->orderBy('id')->value('id');
        return [
            Action::make('previousPromotion')
                ->label('◀ Anterior')
                ->color('gray')
                ->visible((bool) $previousId)
                ->url($previousId ? PromotionResource::getUrl('edit', ['record' => $previousId]) : null),
            Action::make('nextPromotion')
                ->label('Seguinte ▶')
                ->color('gray')
                ->visible((bool) $nextId)
                ->url($nextId ? PromotionResource::getUrl('edit', ['record' => $nextId]) : null),
            DeleteAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\Services\Pages;

use App\Filament\Resources\Services\ServiceResource;
use App\Models\Service;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditService extends EditRecord
{
    protected static string $resource = ServiceResource::class;

    public function getTitle(): string
    {
        return 'Editar Serviço';
    }

    protected function getHeaderActions(): array
    {
        $currentId = $this->getRecord()->id;

        $previousId = Service::where('id', '<', $currentId)->orderByDesc('id')->value('id');
        $nextId = Service::where('id', '>', $currentId)->orderBy('id')->value('id');

        return [
            Action::make('previousService')
                ->label('◀ Anterior')
                ->color('gray')
                ->visible((bool) $previousId)
                ->url($previousId ? ServiceResource::getUrl('edit', ['record' => $previousId]) : null),
            Action::make('nextService')
                ->label('Seguinte ▶')
                ->color('gray')
                ->visible((bool) $nextId)
                ->url($nextId ? ServiceResource::getUrl('edit', ['record' => $nextId]) : null),
            DeleteAction::make()
                ->label('Eliminar'),
        ];
    }
}

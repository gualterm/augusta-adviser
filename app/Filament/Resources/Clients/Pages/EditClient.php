<?php
namespace App\Filament\Resources\Clients\Pages;
use App\Filament\Resources\Clients\ClientResource;
use App\Models\Client;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
class EditClient extends EditRecord
{
    protected static string $resource = ClientResource::class;
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (!empty($data['email']) || !empty($data['phone'])) {
            $data['is_presencial'] = false;
        } else {
            $data['is_presencial'] = true;
        }
        return $data;
    }
    protected function getHeaderActions(): array
    {
        $currentId = $this->getRecord()->id;
        $previousId = Client::where('id', '<', $currentId)->orderByDesc('id')->value('id');
        $nextId = Client::where('id', '>', $currentId)->orderBy('id')->value('id');
        return [
            Action::make('previousClient')
                ->label('◀ Anterior')
                ->color('gray')
                ->visible((bool) $previousId)
                ->url($previousId ? ClientResource::getUrl('edit', ['record' => $previousId]) : null),
            Action::make('nextClient')
                ->label('Seguinte ▶')
                ->color('gray')
                ->visible((bool) $nextId)
                ->url($nextId ? ClientResource::getUrl('edit', ['record' => $nextId]) : null),
            DeleteAction::make(),
        ];
    }
}

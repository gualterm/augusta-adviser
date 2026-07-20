<?php
namespace App\Filament\Resources\OdisseiasServiceMappings\Pages;

use App\Filament\Resources\OdisseiasServiceMappings\OdisseiasServiceMappingResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOdisseiasServiceMapping extends EditRecord
{
    protected static string $resource = OdisseiasServiceMappingResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
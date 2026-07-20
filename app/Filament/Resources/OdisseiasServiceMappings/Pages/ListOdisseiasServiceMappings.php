<?php
namespace App\Filament\Resources\OdisseiasServiceMappings\Pages;

use App\Filament\Resources\OdisseiasServiceMappings\OdisseiasServiceMappingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOdisseiasServiceMappings extends ListRecords
{
    protected static string $resource = OdisseiasServiceMappingResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
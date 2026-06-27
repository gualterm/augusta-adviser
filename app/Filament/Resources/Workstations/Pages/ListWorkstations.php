<?php

namespace App\Filament\Resources\Workstations\Pages;

use App\Filament\Resources\Workstations\WorkstationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWorkstations extends ListRecords
{
    protected static string $resource = WorkstationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

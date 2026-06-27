<?php

namespace App\Filament\Resources\Workstations\Pages;

use App\Filament\Resources\Workstations\WorkstationResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewWorkstation extends ViewRecord
{
    protected static string $resource = WorkstationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}

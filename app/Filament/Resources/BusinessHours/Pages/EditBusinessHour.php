<?php
namespace App\Filament\Resources\BusinessHours\Pages;

use App\Filament\Resources\BusinessHours\BusinessHoursResource;
use Filament\Resources\Pages\EditRecord;

class EditBusinessHour extends EditRecord
{
    protected static string $resource = BusinessHoursResource::class;

    protected function getHeaderActions(): array { return []; }
}
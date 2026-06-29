<?php
namespace App\Filament\Resources\BusinessHours\Pages;

use App\Filament\Resources\BusinessHours\BusinessHoursResource;
use Filament\Resources\Pages\ListRecords;

class ListBusinessHours extends ListRecords
{
    protected static string $resource = BusinessHoursResource::class;
}
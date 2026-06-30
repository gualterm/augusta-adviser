<?php

namespace App\Filament\Resources\Clients\Pages;

use App\Filament\Resources\Clients\ClientResource;
use Filament\Resources\Pages\CreateRecord;

class CreateClient extends CreateRecord
{
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['email']) && empty($data['phone'])) {
            $data['is_presencial'] = true;
        }
        return $data;
    }
    protected static string $resource = ClientResource::class;
}

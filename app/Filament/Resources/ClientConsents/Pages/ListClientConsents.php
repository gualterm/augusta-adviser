<?php
namespace App\Filament\Resources\ClientConsents\Pages;

use App\Filament\Resources\ClientConsents\ClientConsentResource;
use Filament\Resources\Pages\ListRecords;

class ListClientConsents extends ListRecords
{
    protected static string $resource = ClientConsentResource::class;

    protected function getHeaderActions(): array
    {
        return []; // Só leitura — consentimentos criados pelo cliente ou formulário
    }
}
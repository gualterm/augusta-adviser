<?php
namespace App\Filament\Resources\Promotions\Pages;
use App\Filament\Resources\Promotions\PromotionResource;
use Filament\Resources\Pages\CreateRecord;
class CreatePromotion extends CreateRecord
{
    protected static string $resource = PromotionResource::class;

    protected function afterFill(): void
    {
        $serviceId = request()->query('service_id');
        if ($serviceId) {
            $this->form->fill(array_merge($this->form->getRawState(), [
                'service_id' => (int) $serviceId,
            ]));
        }
    }
}

<?php

namespace App\Filament\Resources\ExternalBookings\Pages;

use App\Filament\Resources\ExternalBookings\ExternalBookingResource;
use App\Models\OdisseiasSetting;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Artisan;

class ListExternalBookings extends ListRecords
{
    protected static string $resource = ExternalBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sincronizar_agora')
                ->label('Sincronizar agora')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->action(function () {
                    Artisan::call('odisseias:sync', ['--commit' => true]);
                    $output = Artisan::output();

                    Notification::make()
                        ->title('Sincronização concluída')
                        ->body($output)
                        ->success()
                        ->persistent()
                        ->send();
                }),

            Action::make('toggle_auto_confirm')
                ->label(fn (): string => 'Modo automático: ' . (OdisseiasSetting::current()->auto_confirm ? 'Ligado' : 'Desligado'))
                ->icon(fn (): string => OdisseiasSetting::current()->auto_confirm ? 'heroicon-o-bolt' : 'heroicon-o-bolt-slash')
                ->color(fn (): string => OdisseiasSetting::current()->auto_confirm ? 'success' : 'gray')
                ->requiresConfirmation()
                ->modalDescription(fn (): string => OdisseiasSetting::current()->auto_confirm
                    ? 'Vai DESLIGAR a confirmação automática. As reservas sem conflito vão passar a ficar à espera de confirmação manual.'
                    : 'Vai LIGAR a confirmação automática. A cada sincronização horária, reservas sem conflito de horário são criadas sozinhas na agenda real. A lista vai poder ser filtrada para mostrar só os conflitos/erros que precisam de atenção.')
                ->action(function () {
                    $setting = OdisseiasSetting::current();
                    $setting->update(['auto_confirm' => !$setting->auto_confirm]);

                    Notification::make()
                        ->title('Modo automático ' . ($setting->auto_confirm ? 'ligado' : 'desligado'))
                        ->success()
                        ->send();
                }),
        ];
    }
}

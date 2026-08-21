<?php
namespace App\Filament\Resources\Clients\Pages;
use App\Filament\Resources\Clients\ClientResource;
use App\Models\Client;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
class ListClients extends ListRecords
{
    protected static string $resource = ClientResource::class;
    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportExcel')
                ->label('Exportar Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    return response()->streamDownload(function () {
                        $handle = fopen('php://output', 'w');
                        fwrite($handle, "\xEF\xBB\xBF");
                        fputcsv($handle, ['Nome', 'Género', 'Telefone', 'Email', 'NIF', 'Morada', 'Data Nascimento', 'Como nos conheceu', 'Ativo', 'Criado em'], ';');
                        Client::orderBy('name')->get()->each(function ($client) use ($handle) {
                            fputcsv($handle, [
                                $client->name,
                                match ($client->gender) { 'feminino' => 'Feminino', 'masculino' => 'Masculino', default => '' },
                                $client->phone,
                                $client->email,
                                $client->nif,
                                $client->address,
                                $client->birth_date ? \Carbon\Carbon::parse($client->birth_date)->format('d/m/Y') : '',
                                match ($client->referral_source) { 'facebook' => 'Facebook', 'instagram' => 'Instagram', 'odisseias' => 'Odisseias', 'outro' => ($client->referral_other ?: 'Outro'), default => '' },
                                $client->active ? 'Sim' : 'Não',
                                $client->created_at?->format('d/m/Y H:i'),
                            ], ';');
                        });
                        fclose($handle);
                    }, 'clientes_augusta_adviser_' . now()->format('Y-m-d') . '.csv');
                }),
            CreateAction::make()->visible(fn() => static::getResource()::canCreate()),
        ];
    }
}

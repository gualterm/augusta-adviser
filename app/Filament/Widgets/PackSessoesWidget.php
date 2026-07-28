<?php

namespace App\Filament\Widgets;

use Illuminate\Support\Facades\DB;
use Filament\Widgets\Widget;

class PackSessoesWidget extends Widget
{
    protected static string $view = 'filament.widgets.pack-sessoes';
    protected int|string|array $columnSpan = 'full';
    protected static ?int $sort = 10;

    public function getData(): array
    {
        $rows = DB::table('appointments')
            ->join('clients', 'clients.id', '=', 'appointments.client_id')
            ->join('services', 'services.id', '=', 'appointments.service_id')
            ->where('services.name', 'like', '%6%sess%')
            ->whereNotIn('appointments.status', ['cancelled'])
            ->select(
                'clients.id',
                'clients.name as cliente',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN appointments.status = "completed" THEN 1 ELSE 0 END) as completadas'),
                DB::raw('SUM(CASE WHEN appointments.status IN ("confirmed","scheduled") THEN 1 ELSE 0 END) as agendadas'),
                DB::raw('MAX(appointments.appointment_date) as ultima_data')
            )
            ->groupBy('clients.id', 'clients.name')
            ->orderBy('clients.name')
            ->get()
            ->map(function ($r) {
                $packsCompletos = intdiv((int) $r->completadas, 6);
                $sessoesPack    = ((int) $r->completadas % 6) + (int) $r->agendadas;
                $restantes      = max(0, 6 - $sessoesPack);
                return [
                    'cliente'   => $r->cliente,
                    'pack'      => $packsCompletos + 1,
                    'feitas'    => (int) $r->completadas,
                    'agendadas' => (int) $r->agendadas,
                    'restantes' => $restantes,
                    'ultima'    => $r->ultima_data,
                ];
            })
            ->toArray();

        return ['clientes' => $rows];
    }
}

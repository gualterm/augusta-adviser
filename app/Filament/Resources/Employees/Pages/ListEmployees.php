<?php
namespace App\Filament\Resources\Employees\Pages;
use App\Filament\Resources\Employees\EmployeeResource;
use App\Models\Employee;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
class ListEmployees extends ListRecords
{
    protected static string $resource = EmployeeResource::class;
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
                        fputcsv($handle, ['Nome', 'Função', 'Telefone', 'Email', 'NIF', '% Comissão Padrão', 'Ativo', 'Criado em'], ';');
                        Employee::orderBy('name')->get()->each(function ($employee) use ($handle) {
                            fputcsv($handle, [
                                $employee->name,
                                $employee->role,
                                $employee->phone,
                                $employee->email,
                                $employee->nif,
                                number_format((float) $employee->default_commission_percentage, 2, ',', '') . '%',
                                $employee->active ? 'Sim' : 'Não',
                                $employee->created_at?->format('d/m/Y H:i'),
                            ], ';');
                        });
                        fclose($handle);
                    }, 'profissionais_augusta_adviser_' . now()->format('Y-m-d') . '.csv');
                }),
            CreateAction::make()->visible(fn() => static::getResource()::canCreate()),
        ];
    }
}

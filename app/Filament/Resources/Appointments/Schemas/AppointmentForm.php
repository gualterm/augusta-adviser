<?php

namespace App\Filament\Resources\Appointments\Schemas;

use App\Models\Service;
use App\Models\Workstation;
use App\Services\AppointmentConflictService;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class AppointmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dados da Marcação')
                    ->schema([
                        Select::make('client_id')
                            ->label('Cliente')
                            ->relationship('client', 'name')
                            ->searchable()->preload()
                            ->required(),
                        Select::make('employee_id')
                            ->label('Profissional')
                            ->relationship('employee', 'name')
                            ->searchable()->preload()
                            ->live()
                            ->required(),
                        Select::make('category')
                            ->label('Categoria')
                            ->options(
                                Service::query()
                                    ->distinct()
                                    ->orderBy('category')
                                    ->pluck('category', 'category')
                                    ->toArray()
                            )
                            ->default(function ($record) {
                                if (! $record?->service_id) {
                                    return null;
                                }
                                return Service::find(
                                    $record->service_id
                                )?->category;
                            })
                            ->live()
                            ->dehydrated(false),
                        Select::make('service_id')
                            ->label('Serviço')
                            ->options(function ($get, $record) {
                                $category = $get('category');
                                if (! $category && $record?->service_id) {
                                    $category = Service::find(
                                        $record->service_id
                                    )?->category;
                                }
                                if (! $category) {
                                    return [];
                                }
                                return Service::query()
                                    ->where('category', $category)
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                if (! $state) {
                                    return;
                                }
                                $service = Service::find($state);
                                if (! $service) {
                                    return;
                                }
                                $set('price', $service->price);
                                $set('workstation_id', null);
                                if ($get('appointment_time')) {
                                    $endTime = Carbon::parse(
                                        $get('appointment_time')
                                    )->addMinutes(
                                        $service->duration_minutes
                                    );
                                    $set(
                                        'end_time',
                                        $endTime->format('H:i')
                                    );
                                }
                            })
                            ->searchable()->preload()
                            ->required(),
                        Select::make('workstation_id')
                            ->label('Posto')
                            ->options(function ($get, $record) {
                                $serviceId = $get('service_id')
                                    ?: $record?->service_id;
                                if (! $serviceId) {
                                    return [];
                                }
                                $service = Service::find($serviceId);
                                if (! $service) {
                                    return [];
                                }
                                return Workstation::query()
                                    ->where(
                                        'type',
                                        $service->workstation_type
                                    )
                                    ->where('active', true)
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->live()
                            ->searchable()->preload()
                            ->required(),
                        DatePicker::make('appointment_date')
                            ->label('Data')
                            ->live()
                            ->required(),
                        TimePicker::make('appointment_time')
                            ->label('Hora')
                            ->seconds(false)
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                if (! $state || ! $get('service_id')) {
                                    return;
                                }
                                $service = Service::find(
                                    $get('service_id')
                                );
                                if (! $service) {
                                    return;
                                }
                                $endTime = Carbon::parse($state)
                                    ->addMinutes(
                                        $service->duration_minutes
                                    );
                                $set(
                                    'end_time',
                                    $endTime->format('H:i')
                                );
                            })
                            ->required(),
                        TextInput::make('end_time')
                            ->label('Hora Fim')
                            ->disabled()
                            ->dehydrated(),
                        Select::make('status')
                            ->label('Estado')
                            ->options([
                                'scheduled' => 'Agendada',
                                'confirmed' => 'Confirmada',
                                'completed' => 'Concluída',
                                'cancelled' => 'Cancelada',
                            ])
                            ->default('scheduled')
                            ->required(),
                        TextInput::make('price')
                            ->label('Preço (€)')
                            ->numeric(),
                        Textarea::make('notes')
                            ->label('Notas')
                            ->rows(4),
                        Placeholder::make('conflict_warning')
                            ->label('Verificação de Conflitos')
                            ->content(function ($get, $record) {
                                $employeeId = $get('employee_id');
                                $workstationId = $get('workstation_id');
                                $date = $get('appointment_date');
                                $start = $get('appointment_time');
                                $end = $get('end_time');

                                if (! $employeeId || ! $date || ! $start || ! $end) {
                                    return new HtmlString(
                                        '<span style="color:#9b8a7c;">Preenche profissional, data e hora para verificar disponibilidade.</span>'
                                    );
                                }

                                $ignoreId = $record?->id;
                                $messages = [];

                                if (AppointmentConflictService::employeeHasConflict(
                                    (int) $employeeId,
                                    $date,
                                    $start,
                                    $end,
                                    $ignoreId
                                )) {
                                    $messages[] = 'O profissional selecionado já tem uma marcação que se sobrepõe a este horário.';
                                }

                                if ($workstationId && AppointmentConflictService::workstationHasConflict(
                                    (int) $workstationId,
                                    $date,
                                    $start,
                                    $end,
                                    $ignoreId
                                )) {
                                    $messages[] = 'O posto selecionado já está ocupado nesse horário (será sugerido um posto alternativo automaticamente, se existir).';
                                }

                                if (empty($messages)) {
                                    return new HtmlString(
                                        '<span style="color:#3c6b4a;font-weight:600;">✓ Sem conflitos detetados.</span>'
                                    );
                                }

                                $html = '<div style="color:#b85c5c;font-weight:600;">⚠ '
                                    . implode('<br>⚠ ', $messages)
                                    . '</div><div style="margin-top:6px;font-size:13px;color:#7a6b5d;">'
                                    . 'Podes corrigir o horário/profissional/posto, ou ativar "Forçar Marcação" abaixo para gravar mesmo assim.'
                                    . '</div>';

                                return new HtmlString($html);
                            })
                            ->columnSpanFull(),
                        Toggle::make('force_booking')
                            ->label('Forçar Marcação (ignorar conflito)')
                            ->helperText('Ativa só se quiseres gravar mesmo havendo sobreposição de horário.')
                            ->default(false)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}

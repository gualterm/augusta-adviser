<?php

namespace App\Filament\Widgets;

use App\Models\Appointment;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class TodayAppointmentsWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    protected int|string|array $columnSpan = 'full';
    protected static ?string $heading = 'Marcações de Hoje';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Appointment::query()
                    ->where('appointment_date', Carbon::today()->toDateString())
                    ->whereIn('status', ['scheduled', 'confirmed', 'completed'])
                    ->orderBy('appointment_time')
                    ->with(['client', 'employee', 'service'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('appointment_time')
                    ->label('Hora')
                    ->formatStateUsing(fn ($state) => Carbon::parse($state)->format('H:i'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('client.name')
                    ->label('Cliente')
                    ->searchable(),

                Tables\Columns\TextColumn::make('service.name')
                    ->label('Serviço')
                    ->searchable(),

                Tables\Columns\TextColumn::make('employee.name')
                    ->label('Profissional'),

                Tables\Columns\TextColumn::make('price')
                    ->label('Valor')
                    ->formatStateUsing(fn ($state) => '€ ' . number_format((float)$state, 2, ',', '.')),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Estado')
                    ->colors([
                        'warning'  => 'scheduled',
                        'primary'  => 'confirmed',
                        'success'  => 'completed',
                        'danger'   => 'cancelled',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'scheduled'  => 'Agendada',
                        'confirmed'  => 'Confirmada',
                        'completed'  => 'Concluída',
                        'cancelled'  => 'Cancelada',
                        default      => $state,
                    }),
            ])
            ->emptyStateHeading('Sem marcações para hoje')
            ->emptyStateIcon('heroicon-o-calendar');
    }
}
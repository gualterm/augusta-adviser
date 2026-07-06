<?php

namespace App\Filament\Resources\Appointments;

use App\Filament\Resources\Appointments\Pages\CreateAppointment;
use App\Filament\Resources\Appointments\Pages\EditAppointment;
use App\Filament\Resources\Appointments\Pages\ListAppointments;
use App\Filament\Resources\Appointments\Schemas\AppointmentForm;
use App\Filament\Resources\Appointments\Tables\AppointmentsTable;
use App\Models\Appointment;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use App\Filament\Traits\HasRolePermissions;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AppointmentResource extends Resource
{
    use HasRolePermissions;
    protected static ?string $model = Appointment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Agenda';

    protected static ?string $modelLabel = 'Marcação';

    protected static ?string $pluralModelLabel = 'Agenda';

    protected static string|UnitEnum|null $navigationGroup = 'Operações';

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return AppointmentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AppointmentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    /**
     * Contador na barra lateral para marcações com aviso pendente (ex.: pedidos
     * de horário de almoço) ainda não resolvidos e que ainda não passaram.
     * Pedido da Marta (2026-07-06): não pode passar despercebido.
     */
    public static function getNavigationBadge(): ?string
    {
        $count = Appointment::query()
            ->where('notes', 'like', '%horário de almoço%')
            ->where('status', '!=', 'cancelled')
            ->where('appointment_date', '>=', now()->toDateString())
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAppointments::route('/'),
            'create' => CreateAppointment::route('/create'),
            'edit' => EditAppointment::route('/{record}/edit'),
        ];
    }
}

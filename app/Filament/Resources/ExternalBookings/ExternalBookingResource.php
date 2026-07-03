<?php

namespace App\Filament\Resources\ExternalBookings;

use App\Filament\Resources\ExternalBookings\Pages\ListExternalBookings;
use App\Filament\Resources\ExternalBookings\Tables\ExternalBookingsTable;
use App\Filament\Traits\HasRolePermissions;
use App\Models\ExternalBooking;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * "Marcações Externas" — área separada pedida pela Marta (2026-07-03) para
 * rever e confirmar reservas vindas de canais externos (hoje só a
 * Odisseias) antes de entrarem na agenda real. Nomeada de forma genérica de
 * propósito (não "Odisseias") para reutilizar exatamente a mesma lista, os
 * mesmos filtros e a mesma ação de confirmar quando um canal novo (Google
 * Calendar, Outlook, WhatsApp, ...) for adicionado — decisão tomada ao
 * discutir a evolução da Augusta Adviser para um "hub" multi-canal, mas
 * sem multi-tenant/SaaS, mantendo o foco só na Marta por agora.
 *
 * Ver App\Services\OdisseiasClient (conector do canal Odisseias) e
 * App\Services\ExternalBookingConfirmer (lógica partilhada de confirmação)
 * e App\Console\Commands\SyncOdisseiasBookings (sync horário / botão manual).
 */
class ExternalBookingResource extends Resource
{
    use HasRolePermissions;

    protected static ?string $model = ExternalBooking::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static ?string $navigationLabel = 'Marcações Externas';

    protected static ?string $modelLabel = 'Marcação Externa';

    protected static ?string $pluralModelLabel = 'Marcações Externas';

    protected static string|UnitEnum|null $navigationGroup = 'Operações';

    protected static ?string $recordTitleAttribute = 'client_name';

    public static function table(Table $table): Table
    {
        return ExternalBookingsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListExternalBookings::route('/'),
        ];
    }
}

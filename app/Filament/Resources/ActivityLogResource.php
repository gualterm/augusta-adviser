<?php
namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLog\Pages;
use App\Models\ActivityLog;
use App\Models\Client;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Forms\Components\Select as FormSelect;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;

class ActivityLogResource extends Resource
{
    // Apenas propriedades que o pai também declara como ?string
    protected static ?string $model            = ActivityLog::class;
    protected static ?string $navigationLabel  = 'Registo de Actividade';
    protected static ?string $modelLabel       = 'entrada de log';
    protected static ?string $pluralModelLabel = 'Registo de Actividade';

    // navigationIcon ($navigationIcon é BackedEnum|string|null no pai → usar método)
    // navigationGroup ($navigationGroup é UnitEnum|string|null no pai → usar método)
    public static function getNavigationIcon(): string  { return 'heroicon-o-clipboard-document-list'; }
    public static function getNavigationGroup(): string { return 'Sistema e Log'; }
    public static function getNavigationSort(): ?int    { return 90; }
    public static function canCreate(): bool            { return false; }
    public static function canEdit($record): bool       { return false; }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Data / Hora')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->timezone('Europe/Lisbon'),

                TextColumn::make('event_type')
                    ->label('Evento')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string =>
                        ActivityLog::eventLabels()[$state] ?? $state)
                    ->color(fn (string $state): string => match (true) {
                        str_contains($state, 'client')    => 'info',
                        str_contains($state, 'created')   => 'success',
                        str_contains($state, 'cancelled') => 'danger',
                        str_contains($state, 'reschedul') => 'warning',
                        default                            => 'gray',
                    }),

                TextColumn::make('source')
                    ->label('Origem')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string =>
                        ActivityLog::sourceLabels()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'portal'   => 'primary',
                        'admin'    => 'warning',
                        'rgpd'     => 'gray',
                        'external' => 'info',
                        default    => 'gray',
                    }),

                TextColumn::make('actor_name')
                    ->label('Actor')
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('description')
                    ->label('Descrição')
                    ->wrap()
                    ->searchable(),

                TextColumn::make('subject_id')
                    ->label('ID')
                    ->formatStateUsing(function ($state, $record) {
                        if (!$state) return '—';
                        return match ($record->subject_type) {
                            'client'      => "C#{$state}",
                            'appointment' => "M#{$state}",
                            default       => "#{$state}",
                        };
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Filter::make('cliente')
                    ->label('Cliente')
                    ->form([
                        Select::make('client_id')
                            ->label('Pesquisar cliente')
                            ->placeholder('Escolhe ou pesquisa...')
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search) =>
                                Client::where('name', 'like', "%{$search}%")
                                    ->orWhere('email', 'like', "%{$search}%")
                                    ->limit(20)
                                    ->pluck('name', 'id')
                                    ->toArray()
                            )
                            ->getOptionLabelUsing(fn ($value) =>
                                Client::find($value)?->name ?? $value
                            ),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['client_id'])) return $query;
                        $clientId = $data['client_id'];
                        $apptIds  = \App\Models\Appointment::where('client_id', $clientId)->pluck('id');
                        return $query->where(function ($q) use ($clientId, $apptIds) {
                            $q->where(function ($q2) use ($clientId) {
                                $q2->where('subject_type', 'client')->where('subject_id', $clientId);
                            })->orWhere(function ($q2) use ($apptIds) {
                                $q2->where('subject_type', 'appointment')->whereIn('subject_id', $apptIds);
                            });
                        });
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (empty($data['client_id'])) return null;
                        $name = Client::find($data['client_id'])?->name;
                        return $name ? "Cliente: {$name}" : null;
                    }),

                SelectFilter::make('event_type')
                    ->label('Evento')
                    ->options(ActivityLog::eventLabels())
                    ->placeholder('Todos os eventos'),

                SelectFilter::make('source')
                    ->label('Origem')
                    ->options(ActivityLog::sourceLabels())
                    ->placeholder('Todas as origens'),

                Filter::make('periodo')
                    ->label('Período')
                    ->form([
                        DatePicker::make('from')->label('De')->displayFormat('d/m/Y'),
                        DatePicker::make('until')->label('Até')->displayFormat('d/m/Y'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'],  fn ($q) => $q->whereDate('created_at', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->whereDate('created_at', '<=', $data['until']));
                    })
                    ->indicateUsing(function (array $data): array {
                        $i = [];
                        if ($data['from'])  $i[] = 'De: '  . \Carbon\Carbon::parse($data['from'])->format('d/m/Y');
                        if ($data['until']) $i[] = 'Até: ' . \Carbon\Carbon::parse($data['until'])->format('d/m/Y');
                        return $i;
                    }),
            ])
            ->filtersLayout(\Filament\Tables\Enums\FiltersLayout::AboveContent)
            ->paginated([25, 50, 100])
            ->poll('60s');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
        ];
    }
}
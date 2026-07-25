<?php
namespace App\Filament\Resources\ActivityLog\Pages;

use App\Filament\Resources\ActivityLogResource;
use App\Filament\Widgets\ActivityStatsWidget;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\HtmlString;

class ListActivityLogs extends ListRecords
{
    protected static string $resource = ActivityLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('limpar_bloco')
                ->label('Limpar bloco')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->outlined()
                ->form([
                    // ── Critérios ──────────────────────────────────────────
                    Select::make('client_id')
                        ->label('Cliente (opcional)')
                        ->placeholder('Todos os clientes')
                        ->nullable()
                        ->live()
                        ->searchable()
                        ->getSearchResultsUsing(fn (string $search) =>
                            \App\Models\Client::where('name', 'like', "%{$search}%")
                                ->limit(20)
                                ->pluck('name', 'id')
                                ->toArray()
                        )
                        ->getOptionLabelUsing(fn ($value) =>
                            \App\Models\Client::find($value)?->name ?? $value
                        ),

                    Select::make('event_type')
                        ->label('Tipo de evento')
                        ->options(\App\Models\ActivityLog::eventLabels())
                        ->placeholder('Todos os eventos')
                        ->nullable()
                        ->live(),

                    Select::make('source')
                        ->label('Origem')
                        ->options(\App\Models\ActivityLog::sourceLabels())
                        ->placeholder('Todas as origens')
                        ->nullable()
                        ->live(),

                    Select::make('backfill_only')
                        ->label('Apenas registos de importação (backfill)?')
                        ->options([
                            '1' => 'Sim — só registos marcados como backfill',
                            '0' => 'Não — todos os que correspondam aos filtros',
                        ])
                        ->default('0')
                        ->live(),

                    DatePicker::make('from')
                        ->label('De (data)')
                        ->displayFormat('d/m/Y')
                        ->nullable()
                        ->live(),

                    DatePicker::make('until')
                        ->label('Até (data)')
                        ->displayFormat('d/m/Y')
                        ->nullable()
                        ->live(),

                    // ── Pré-visualização dinâmica ──────────────────────────
                    Placeholder::make('preview')
                        ->label('Pré-visualização')
                        ->columnSpanFull()
                        ->content(function (callable $get): HtmlString {
                            $query = DB::table('activity_logs');

                            if (!empty($get('client_id'))) {
                                $query->where('client_id', $get('client_id'));
                            }
                            if (!empty($get('event_type'))) {
                                $query->where('event_type', $get('event_type'));
                            }
                            if (!empty($get('source'))) {
                                $query->where('source', $get('source'));
                            }
                            if (($get('backfill_only') ?? '0') === '1') {
                                $query->whereRaw("JSON_EXTRACT(metadata, '$.backfill') = true");
                            }
                            if (!empty($get('from'))) {
                                $query->whereDate('created_at', '>=', $get('from'));
                            }
                            if (!empty($get('until'))) {
                                $query->whereDate('created_at', '<=', $get('until'));
                            }

                            $count = $query->count();

                            if ($count === 0) {
                                return new HtmlString(
                                    '<div style="padding:12px 16px;background:#fdf6ee;border:1px solid #c9a96e;border-radius:6px;color:#6b5b4e;">'
                                    . '⚪ Nenhum registo encontrado com estes critérios.'
                                    . '</div>'
                                );
                            }

                            // Clientes afectados (top 8 do campo description)
                            $samples = (clone $query)
                                ->orderBy('created_at', 'desc')
                                ->limit(8)
                                ->pluck('description')
                                ->map(fn($d) => '• ' . $d)
                                ->implode('<br>');

                            $color  = $count > 50 ? '#b85c5c' : ($count > 10 ? '#c9760a' : '#3c6b4a');
                            $icon   = $count > 50 ? '⛔' : ($count > 10 ? '⚠️' : '🗑️');

                            return new HtmlString(
                                '<div style="padding:12px 16px;background:#fdf6ee;border:1px solid ' . $color . ';border-radius:6px;">'
                                . '<div style="font-weight:700;font-size:15px;color:' . $color . ';margin-bottom:8px;">'
                                . $icon . '  ' . $count . ' registo' . ($count !== 1 ? 's' : '') . ' serão eliminados permanentemente'
                                . '</div>'
                                . '<div style="font-size:12px;color:#6b5b4e;line-height:1.6;">'
                                . ($count > 8 ? 'Últimos 8 registos (de ' . $count . '):<br>' : 'Registos a eliminar:<br>')
                                . $samples
                                . '</div>'
                                . '</div>'
                            );
                        }),

                    // ── Autenticação ───────────────────────────────────────
                    TextInput::make('admin_password')
                        ->label('Password de administrador')
                        ->helperText('Introduz a tua password para confirmar. A operação será registada no log.')
                        ->password()
                        ->revealable()
                        ->required()
                        ->columnSpanFull(),
                ])
                ->action(function (array $data): void {
                    // 1. Verificar password
                    $user = auth()->user();
                    if (!$user || !Hash::check($data['admin_password'], $user->password)) {
                        Notification::make()
                            ->title('Password incorrecta')
                            ->body('Nenhum registo foi eliminado.')
                            ->danger()
                            ->persistent()
                            ->send();
                        return;
                    }

                    // 2. Query com critérios
                    $query = DB::table('activity_logs');
                    if (!empty($data['client_id']))   $query->where('client_id', $data['client_id']);
                    if (!empty($data['event_type']))  $query->where('event_type', $data['event_type']);
                    if (!empty($data['source']))       $query->where('source', $data['source']);
                    if (($data['backfill_only'] ?? '0') === '1') {
                        $query->whereRaw("JSON_EXTRACT(metadata, '$.backfill') = true");
                    }
                    if (!empty($data['from']))  $query->whereDate('created_at', '>=', $data['from']);
                    if (!empty($data['until'])) $query->whereDate('created_at', '<=', $data['until']);

                    $count = $query->count();
                    if ($count === 0) {
                        Notification::make()->title('Nenhum registo encontrado')->warning()->send();
                        return;
                    }

                    $query->delete();

                    // 3. Registar eliminação no log
                    \App\Services\ActivityLogger::log(
                        eventType: 'log.cleared',
                        source: 'admin',
                        actorName: $user->name,
                        description: "$count entradas eliminadas do log pelo administrador",
                        metadata: [
                            'event_type'    => $data['event_type']    ?? null,
                            'source'        => $data['source']         ?? null,
                            'backfill_only' => ($data['backfill_only'] ?? '0') === '1',
                            'from'          => $data['from']  ?? null,
                            'until'         => $data['until'] ?? null,
                            'deleted_count' => $count,
                        ]
                    );

                    Notification::make()
                        ->title("$count entradas eliminadas")
                        ->body('Operação registada no log de actividade.')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading('Limpar bloco de registos')
                ->modalDescription('Define os critérios — verás quantos registos serão afectados antes de confirmar com a password.')
                ->modalSubmitActionLabel('Eliminar permanentemente'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [ActivityStatsWidget::class];
    }
}
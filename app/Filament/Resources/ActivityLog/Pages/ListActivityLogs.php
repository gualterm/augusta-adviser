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
                    // ── Critérios de selecção ──────────────────────────────
                    Select::make('event_type')
                        ->label('Tipo de evento')
                        ->options(\App\Models\ActivityLog::eventLabels())
                        ->placeholder('Todos os eventos')
                        ->nullable(),

                    Select::make('source')
                        ->label('Origem')
                        ->options(\App\Models\ActivityLog::sourceLabels())
                        ->placeholder('Todas as origens')
                        ->nullable(),

                    Select::make('backfill_only')
                        ->label('Apenas registos de importação (backfill)?')
                        ->options([
                            '1' => 'Sim — só registos marcados como backfill',
                            '0' => 'Não — todos os que correspondam aos filtros',
                        ])
                        ->default('0'),

                    DatePicker::make('from')
                        ->label('De (data)')
                        ->displayFormat('d/m/Y')
                        ->nullable(),

                    DatePicker::make('until')
                        ->label('Até (data)')
                        ->displayFormat('d/m/Y')
                        ->nullable(),

                    // ── Autenticação obrigatória ───────────────────────────
                    TextInput::make('admin_password')
                        ->label('Password de administrador')
                        ->password()
                        ->required()
                        ->helperText('Introduz a tua password para autorizar esta eliminação permanente.')
                        ->revealable(),
                ])
                ->action(function (array $data): void {
                    // ── 1. Verificar password ──────────────────────────────
                    $user = auth()->user();
                    if (!$user || !Hash::check($data['admin_password'], $user->password)) {
                        Notification::make()
                            ->title('Password incorrecta')
                            ->body('A operação foi cancelada. Nenhum registo foi eliminado.')
                            ->danger()
                            ->persistent()
                            ->send();
                        return;
                    }

                    // ── 2. Construir query com critérios ───────────────────
                    $query = DB::table('activity_logs');

                    if (!empty($data['event_type'])) {
                        $query->where('event_type', $data['event_type']);
                    }
                    if (!empty($data['source'])) {
                        $query->where('source', $data['source']);
                    }
                    if (($data['backfill_only'] ?? '0') === '1') {
                        $query->whereRaw("JSON_EXTRACT(metadata, '$.backfill') = true");
                    }
                    if (!empty($data['from'])) {
                        $query->whereDate('created_at', '>=', $data['from']);
                    }
                    if (!empty($data['until'])) {
                        $query->whereDate('created_at', '<=', $data['until']);
                    }

                    // ── 3. Preview → eliminar ──────────────────────────────
                    $count = $query->count();

                    if ($count === 0) {
                        Notification::make()
                            ->title('Nenhum registo encontrado')
                            ->body('Os critérios definidos não correspondem a nenhuma entrada.')
                            ->warning()
                            ->send();
                        return;
                    }

                    $query->delete();

                    // ── 4. Registar a própria eliminação no log ────────────
                    \App\Services\ActivityLogger::log(
                        eventType: 'log.cleared',
                        source:    'admin',
                        actorName: $user->name,
                        description: "$count entradas eliminadas do log pelo administrador",
                        metadata: [
                            'event_type'    => $data['event_type'] ?? null,
                            'source'        => $data['source']     ?? null,
                            'backfill_only' => $data['backfill_only'] === '1',
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
                ->modalDescription('Define os critérios e confirma com a tua password. Esta operação é irreversível.')
                ->modalSubmitActionLabel('Eliminar registos permanentemente'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ActivityStatsWidget::class,
        ];
    }
}
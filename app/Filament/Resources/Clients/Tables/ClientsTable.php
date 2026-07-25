<?php
namespace App\Filament\Resources\Clients\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ClientsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('is_presencial')
                    ->label('')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? '⚠ Incompleto' : '')
                    ->color(fn ($state) => $state ? 'warning' : null),
                TextColumn::make('gender')
                    ->label('Género')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'feminino'  => '♀ Feminino',
                        'masculino' => '♂ Masculino',
                        default     => '—',
                    })
                    ->sortable(),
                TextColumn::make('phone')
                    ->label('Telefone')
                    ->searchable(),
TextColumn::make('referral_source')
                    ->label('Origem')
                    ->formatStateUsing(fn($state) => match($state) {
                        'facebook'  => 'Facebook',
                        'instagram' => 'Instagram',
                        'odisseias' => 'Odisseias',
                        'outro'     => 'Outro',
                        default     => '—',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                TextColumn::make('nif')
                    ->label('NIF')
                    ->searchable(),
                IconColumn::make('active')
                    ->label('Ativo')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Criado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('is_presencial', 'desc')
            ->recordClasses(fn ($record) => $record->is_presencial ? 'bg-amber-50' : null)
            ->filters([
                SelectFilter::make('gender')
                    ->label('Género')
                    ->options([
                        'feminino'  => 'Feminino',
                        'masculino' => 'Masculino',
                    ]),
            ])
            ->recordActions([
                EditAction::make()->visible(
                    fn ($record) => \App\Filament\Resources\Clients\ClientResource::canEdit($record)
                ),
                Action::make('delete_completely')
                    ->label('Eliminar completamente')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->modalHeading('Eliminar cliente completamente')
                    ->modalDescription('Esta acção é irreversível. Serão eliminados o cliente, todas as marcações e todos os registos de actividade.')
                    ->modalSubmitActionLabel('Eliminar permanentemente')
                    ->form(fn ($record) => [
                        Placeholder::make('preview_info')
                            ->label('O que será eliminado')
                            ->content(function () use ($record) {
                                $appts = $record->appointments()->count();
                                $logs  = \App\Models\ActivityLog::where('client_id', $record->id)->count();
                                $total = $appts + $logs;
                                $bg    = $total > 20 ? '#fef2f2' : '#fff7ed';
                                $bd    = $total > 20 ? '#fca5a5' : '#fdba74';
                                return new \Illuminate\Support\HtmlString(
                                    "<div style='padding:12px;background:{$bg};border:1px solid {$bd};border-radius:6px;font-size:13px;'>" .
                                    "<p style='font-weight:700;color:#b91c1c;margin-bottom:8px;'>&#9888; Aten&ccedil;&atilde;o &mdash; ac&ccedil;&atilde;o irrevers&iacute;vel</p>" .
                                    "<p><strong>Cliente:</strong> " . e($record->name) . "</p>" .
                                    "<p><strong>Marca&ccedil;&otilde;es a eliminar:</strong> {$appts}</p>" .
                                    "<p><strong>Registos de log a eliminar:</strong> {$logs}</p>" .
                                    "</div>"
                                );
                            }),
                        TextInput::make('admin_password')
                            ->label('Password de Administrador')
                            ->password()
                            ->required()
                            ->helperText('Introduza a sua password para confirmar a elimina\u00e7\u00e3o permanente.'),
                    ])
                    ->action(function ($record, array $data) {
                        if (!\Illuminate\Support\Facades\Hash::check($data['admin_password'], auth()->user()->password)) {
                            \Filament\Notifications\Notification::make()
                                ->title('Password incorrecta')
                                ->body('Verifique a password e tente novamente.')
                                ->danger()
                                ->send();
                            return;
                        }
                        $clientName = $record->name;
                        $clientId   = $record->id;
                        $appts      = $record->appointments()->count();
                        $logs       = \App\Models\ActivityLog::where('client_id', $clientId)->count();

                        \App\Services\ActivityLogger::log(
                            eventType:   'client.deleted_completely',
                            source:      'admin',
                            actorName:   auth()->user()->name,
                            subjectType: 'client',
                            subjectId:   null,
                            description: "Cliente '{$clientName}' eliminado completamente ({$appts} marca\u00e7\u00f5es, {$logs} registos de log)",
                        );

                        \App\Models\ActivityLog::where('client_id', $clientId)->delete();
                        $record->appointments()->delete();
                        $record->delete();

                        \Filament\Notifications\Notification::make()
                            ->title("Cliente eliminado")
                            ->body("'{$clientName}': {$appts} marca\u00e7\u00f5es e {$logs} registos de log eliminados.")
                            ->success()
                            ->send();
                    })
                    ->visible(fn () => \App\Filament\Resources\Clients\ClientResource::canDeleteAny()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->visible(
                        fn () => \App\Filament\Resources\Clients\ClientResource::canDeleteAny()
                    ),
                ]),
            ]);
    }
}
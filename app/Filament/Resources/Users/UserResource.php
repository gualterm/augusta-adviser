<?php
namespace App\Filament\Resources\Users;
use App\Models\Employee;
use App\Models\User;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use App\Filament\Traits\HasRolePermissions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
class UserResource extends Resource
{
    use HasRolePermissions;
    protected static ?string $model = User::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;
    protected static ?string $navigationLabel = 'Utilizadores';
    protected static ?string $modelLabel = 'Utilizador';
    protected static ?string $pluralModelLabel = 'Utilizadores';
    public static function canAccess(): bool { return auth()->user()?->isAdmin() ?? false; }
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Conta de acesso')->schema([
                TextInput::make('name')->label('Nome')->required()->maxLength(255),
                TextInput::make('email')->label('Email')->email()->required()
                    ->unique(ignoreRecord: true)->maxLength(255)
                    ->validationMessages(['unique' => 'Este email já está associado a outro utilizador.']),
                TextInput::make('phone')->label('Telefone')->tel()->maxLength(50)
                    ->unique(ignoreRecord: true)
                    ->validationMessages(['unique' => 'Este telefone já está associado a outro utilizador.']),
                TextInput::make('nif')->label('NIF')->maxLength(20)
                    ->unique(ignoreRecord: true)
                    ->validationMessages(['unique' => 'Este NIF já está associado a outro utilizador.']),
                Select::make('role')->label('Perfil')
                    ->options([
                        'admin'        => '🔑 Administrador',
                        'profissional' => '💆 Profissional',
                        'rececionista' => '📋 Rececionista',
                    ])->required()->default('profissional')->live(),
                TextInput::make('password')->label('Password')->password()
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->minLength(8)
                    ->hint('Deixe em branco para manter a password atual')->hintColor('gray'),
            ])->columns(2),
            Section::make('Morada')->schema([
                TextInput::make('morada')->label('Morada')->maxLength(255)->columnSpanFull(),
                TextInput::make('codigo_postal')->label('Código Postal')->maxLength(10)->placeholder('0000-000'),
                TextInput::make('localidade')->label('Localidade')->maxLength(100),
            ])->columns(2),
            Section::make('Perfil Profissional')
                ->description('Cria ou atualiza automaticamente o registo em Profissionais.')
                ->schema([
                    TextInput::make('_specialty')->label('Especialidade')->maxLength(100)
                        ->dehydrated(false)
                        ->datalist(
                            Employee::query()->whereNotNull('role')->where('role','!=','')
                                ->distinct()->orderBy('role')->pluck('role')->toArray()
                        )
                        ->placeholder('Ex: Esteticista, Massagista, Manicure...'),
                    TextInput::make('_commission')->label('% Comissão Padrão')
                        ->numeric()->suffix('%')->default(0)->minValue(0)->maxValue(100)
                        ->dehydrated(false),
                    Toggle::make('_active')->label('Ativo')->default(true)->dehydrated(false),
                ])->columns(2)
                ->visible(fn ($get) => $get('role') === 'profissional'),
        ]);
    }
    public static function table(Table $table): Table
    {
        return $table->columns([
                TextColumn::make('name')->label('Nome')->searchable()->sortable(),
                TextColumn::make('email')->label('Email')->searchable(),
                TextColumn::make('phone')->label('Telefone')->searchable()->placeholder('—'),
                TextColumn::make('nif')->label('NIF')->searchable()->placeholder('—'),
                TextColumn::make('employee.name')->label('Profissional')->placeholder('—'),
                TextColumn::make('role')->label('Perfil')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'admin' => '🔑 Administrador', 'profissional' => '💆 Profissional',
                        'rececionista' => '📋 Rececionista', default => $state,
                    })->badge()->color(fn ($state) => match ($state) {
                        'admin' => 'danger', 'profissional' => 'success',
                        'rececionista' => 'info', default => 'gray',
                    }),
                TextColumn::make('created_at')->label('Criado em')->date('d/m/Y')->sortable(),
            ])->defaultSort('name')
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
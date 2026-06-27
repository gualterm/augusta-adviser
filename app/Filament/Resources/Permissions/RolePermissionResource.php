<?php
namespace App\Filament\Resources\Permissions;
use App\Models\RolePermission;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use BackedEnum;
use UnitEnum;
use Filament\Schemas\Schema;
class RolePermissionResource extends Resource
{
    protected static ?string $model = RolePermission::class;
    protected static string|BackedEnum|null $navigationIcon  = 'heroicon-o-shield-check';
    protected static ?string $navigationLabel = 'Permissoes';
    protected static string|UnitEnum|null $navigationGroup = 'Administracao';
    protected static ?int    $navigationSort  = 99;
    protected static ?string $modelLabel      = 'Permissao';
    protected static ?string $pluralModelLabel = 'Permissoes';
    public static function canViewAny(): bool { return auth()->user()?->role === 'admin'; }
    public static function canCreate(): bool  { return false; }
    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool { return false; }
    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('role')->label('Perfil')->badge()
                ->color(fn(string $state)=>match($state){'rececionista'=>'info','profissional'=>'success',default=>'gray'})
                ->formatStateUsing(fn(string $state)=>match($state){'rececionista'=>'Rececionista','profissional'=>'Profissional',default=>ucfirst($s)})
                ->sortable(),
            TextColumn::make('resource')->label('Recurso')
                ->formatStateUsing(fn(string $state)=>match($state){
                    'appointment'=>'Marcacoes','client'=>'Clientes','employee'=>'Profissionais',
                    'equipment'=>'Equipamentos','inquiry'=>'Inqueritos','promotion'=>'Promocoes',
                    'service'=>'Servicos','user'=>'Utilizadores','workstation'=>'Postos',
                    'roomavailability'=>'Disponibilidade','weeklycalendar'=>'Calendario',default=>$s})
                ->sortable(),
            ToggleColumn::make('can_view')->label('Ver')->afterStateUpdated(fn()=>RolePermission::clearCache()),
            ToggleColumn::make('can_create')->label('Criar')->afterStateUpdated(fn()=>RolePermission::clearCache()),
            ToggleColumn::make('can_edit')->label('Editar')->afterStateUpdated(fn()=>RolePermission::clearCache()),
            ToggleColumn::make('can_delete')->label('Eliminar')->afterStateUpdated(fn()=>RolePermission::clearCache()),
        ])
        ->filters([SelectFilter::make('role')->label('Perfil')->options(['rececionista'=>'Rececionista','profissional'=>'Profissional'])])
        ->defaultSort('role')->paginated(false)->striped();
    }
    public static function form(Schema $schema): Schema { return $schema->components([]); }
    public static function getPages(): array
    {
        return ['index'=>\App\Filament\Resources\Permissions\Pages\ListRolePermissions::route('/')];
    }
}

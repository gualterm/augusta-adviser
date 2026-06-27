<?php
namespace App\Filament\Traits;
use App\Models\RolePermission;
use Illuminate\Database\Eloquent\Model;
trait HasRolePermissions
{
    public static function canViewAny(): bool  { return static::checkPerm('view'); }
    public static function canCreate(): bool   { return static::checkPerm('create'); }
    public static function canEdit(Model $record): bool   { return static::checkPerm('edit'); }
    public static function canDelete(Model $record): bool { return static::checkPerm('delete'); }
    public static function canDeleteAny(): bool { return static::checkPerm('delete'); }
    protected static function checkPerm(string $action): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        if ($user->role === 'admin') return true;
        return RolePermission::check($user->role, static::getPermissionSlug(), $action);
    }
    protected static function getPermissionSlug(): string
    {
        return strtolower(str_replace('Resource', '', class_basename(static::class)));
    }
}

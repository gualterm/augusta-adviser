<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
class RolePermission extends Model
{
    protected $fillable = ['role','resource','can_view','can_create','can_edit','can_delete'];
    protected $casts = ['can_view'=>'boolean','can_create'=>'boolean','can_edit'=>'boolean','can_delete'=>'boolean'];
    public static function check(string $role, string $resource, string $action): bool
    {
        if ($role === 'admin') return true;
        $key = "roleperm_{$role}_{$resource}_{$action}";
        return Cache::remember($key, 300, function () use ($role, $resource, $action) {
            $perm = static::where('role', $role)->where('resource', $resource)->first();
            return $perm ? (bool) $perm->{"can_{$action}"} : false;
        });
    }
    public static function clearCache(): void { Cache::flush(); }
}

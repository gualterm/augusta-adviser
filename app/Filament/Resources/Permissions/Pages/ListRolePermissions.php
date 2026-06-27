<?php
namespace App\Filament\Resources\Permissions\Pages;
use App\Filament\Resources\Permissions\RolePermissionResource;
use Filament\Resources\Pages\ListRecords;
class ListRolePermissions extends ListRecords
{
    protected static string $resource = RolePermissionResource::class;
    protected function getHeaderActions(): array { return []; }
}

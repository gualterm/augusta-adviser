<?php
namespace Database\Seeders;
use App\Models\RolePermission;
use Illuminate\Database\Seeder;
class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'rececionista' => [
                ['appointment',true,true,true,false],['client',true,true,true,false],
                ['employee',false,false,false,false],['equipment',false,false,false,false],
                ['inquiry',false,false,false,false],['promotion',false,false,false,false],
                ['service',false,false,false,false],['user',false,false,false,false],
                ['workstation',false,false,false,false],
                ['roomavailability',true,false,false,false],['weeklycalendar',true,false,false,false],
            ],
            'profissional' => [
                ['appointment',true,true,true,false],['client',true,false,false,false],
                ['employee',false,false,false,false],['equipment',false,false,false,false],
                ['inquiry',false,false,false,false],['promotion',false,false,false,false],
                ['service',true,false,false,false],['user',false,false,false,false],
                ['workstation',true,false,false,false],
                ['roomavailability',true,false,false,false],['weeklycalendar',true,false,false,false],
            ],
        ];
        foreach ($defaults as $role => $resources) {
            foreach ($resources as [$resource,$view,$create,$edit,$delete]) {
                RolePermission::updateOrCreate(
                    ['role'=>$role,'resource'=>$resource],
                    ['can_view'=>$view,'can_create'=>$create,'can_edit'=>$edit,'can_delete'=>$delete]
                );
            }
        }
        $this->command->info('Role permissions seeded.');
    }
}

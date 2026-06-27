<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Service;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        Appointment::query()->delete();
        Client::query()->delete();
        Employee::query()->delete();

        $clients = [];

        $names = [
            'Ana Silva',
            'Maria Santos',
            'Joana Costa',
            'Patrícia Ferreira',
            'Sofia Lopes',
            'Carla Martins',
            'Marta Almeida',
            'Rita Pereira',
            'Andreia Gomes',
            'Catarina Rocha',
            'Helena Sousa',
            'Inês Correia',
            'Daniela Pinto',
            'Sara Carvalho',
            'Liliana Mendes',
            'Teresa Oliveira',
            'Filipa Cruz',
            'Mónica Ribeiro',
            'Paula Fernandes',
            'Vera Teixeira',
            'Cláudia Antunes',
            'Sandra Moreira',
            'Raquel Cardoso',
            'Cristina Neves',
            'Margarida Fonseca',
        ];

        foreach ($names as $i => $name) {

            $clients[] = Client::create([
                'name' => $name,
                'phone' => '91' . rand(1000000, 9999999),
                'email' => 'cliente' . ($i + 1) . '@teste.pt',
                'birth_date' => now()->subYears(rand(18, 70)),
                'nif' => rand(100000000, 299999999),
                'address' => 'Rua Exemplo ' . ($i + 1),
                'active' => true,
            ]);
        }

        $employees = [];

        $employees[] = Employee::create([
            'name' => 'Marta Almeida',
            'role' => 'Esteticista',
            'phone' => '912345111',
            'email' => 'marta@augusta.pt',
            'active' => true,
        ]);

        $employees[] = Employee::create([
            'name' => 'Joana Silva',
            'role' => 'Massagista',
            'phone' => '912345222',
            'email' => 'joana@augusta.pt',
            'active' => true,
        ]);

        $employees[] = Employee::create([
            'name' => 'Carla Santos',
            'role' => 'Laser',
            'phone' => '912345333',
            'email' => 'carla@augusta.pt',
            'active' => true,
        ]);

        $employees[] = Employee::create([
            'name' => 'Andreia Costa',
            'role' => 'Manicure',
            'phone' => '912345444',
            'email' => 'andreia@augusta.pt',
            'active' => true,
        ]);

        $employees[] = Employee::create([
            'name' => 'Rita Ferreira',
            'role' => 'Receção',
            'phone' => '912345555',
            'email' => 'rita@augusta.pt',
            'active' => true,
        ]);

        $services = Service::all();

        for ($i = 0; $i < 100; $i++) {

            $service = $services->random();

            Appointment::create([
                'client_id' => collect($clients)->random()->id,
                'employee_id' => collect($employees)->random()->id,
                'service_id' => $service->id,

                'appointment_date' => now()
                    ->addDays(rand(0, 30))
                    ->format('Y-m-d'),

                'appointment_time' =>
                    str_pad(rand(9, 18), 2, '0', STR_PAD_LEFT)
                    . ':00:00',

                'status' => collect([
                    'scheduled',
                    'confirmed',
                    'completed',
                ])->random(),

                'price' => $service->price,

                'notes' => null,
            ]);
        }
    }
}

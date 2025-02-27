<?php

namespace Database\Seeders\Clients;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TasksTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('tasks')->insert([
            [
                'client_dbusers_id' => 1,
                'description' => 'Fix the lights in the shop',
                'status' => 'Open',
                'priority' => 'Medium',
                'time_bound' => true,
                'department_id' => 1,
                'closed_on' => null,
                'deadline' => now()->addDays(3), // Example deadline: 3 days from now
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'client_dbusers_id' => 1,
                'description' => 'Restock shelves',
                'status' => 'In Progress',
                'priority' => 'High',
                'time_bound' => false,
                'department_id' => 1,
                'closed_on' => null,
                'deadline' => now()->addDays(7), // Example deadline: 7 days from now
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}

<?php

namespace Database\Seeders\Clients;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UsersTasksTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('user_tasks')->insert([
            [
                'task_id' => 1, 
                'client_dbusers_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'task_id' => 2, 
                'client_dbusers_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],

        ]);
    }
}

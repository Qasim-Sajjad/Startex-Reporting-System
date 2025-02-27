<?php

namespace Database\Seeders\Clients;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('client_dbusers')->insert([
            [
                'name' => 'John Doe',
                'email' => 'john.doe@client1.com',
                'password' => Hash::make('password123'),
                'role' => 'User',
                'department_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}

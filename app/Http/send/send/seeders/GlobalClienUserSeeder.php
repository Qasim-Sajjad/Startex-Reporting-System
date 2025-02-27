<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class GlobalClienUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('globalusersclients')->insert([
            [
                'client_id' => '1',
                'email' => 'john.doe@client1.com',
                'password' => Hash::make('password123'),
                'database_name' => 'client_1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}

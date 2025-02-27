<?php

namespace Database\Seeders\Clients;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LocationsTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('locations')->insert([
            ['name' => 'Headquarters', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Branch Office', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}

<?php

namespace Database\Seeders\Clients;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HierarchynamesTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('hierarchynames')->insert([
            ['name' => 'Corporate Hierarchy', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}

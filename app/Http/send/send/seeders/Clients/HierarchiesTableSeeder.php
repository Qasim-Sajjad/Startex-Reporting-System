<?php

namespace Database\Seeders\Clients;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HierarchiesTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('hierarchies')->insert([
            [
                'location_id' => 1, // Headquarters
                'hierarchylevels_id' => 1, // Level 1
                'branch_code' => 'HQ001',
                'address' => '123 Corporate Lane',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'location_id' => 2, // Branch Office
                'hierarchylevels_id' => 2, // Level 2
                'branch_code' => 'BR001',
                'address' => '456 Branch Ave',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}

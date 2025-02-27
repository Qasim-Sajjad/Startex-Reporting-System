<?php

namespace Database\Seeders\Clients;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HierarchylevelsTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('hierarchylevels')->insert([
            ['name' => 'Level 1', 'level' => 1, 'hierarchynames_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Level 2', 'level' => 2, 'hierarchynames_id' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}

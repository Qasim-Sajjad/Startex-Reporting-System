<?php

namespace Database\Seeders\Clients;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FrequenciesTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('frequencies')->insert([
            ['name' => 'Daily', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Weekly', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Monthly', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}

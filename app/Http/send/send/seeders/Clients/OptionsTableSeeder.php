<?php

namespace Database\Seeders\Clients;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OptionsTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('options')->insert([
            ['name' => 'Excellent', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Good', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Average', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Poor', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}

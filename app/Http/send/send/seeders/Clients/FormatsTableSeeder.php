<?php

namespace Database\Seeders\Clients;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FormatsTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('formats')->insert([
            ['name' => 'PDF', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Excel', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}

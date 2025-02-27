<?php

namespace Database\Seeders\Clients;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SectionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('sections')->insert([
            [
                'format_id' => 1,
                'name' => 'General Information',
                'order_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'format_id' => 1,
                'name' => 'Feedback Section',
                'order_by' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        
    }
}

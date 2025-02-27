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
                'format_id' => 1, // Assuming Format ID 1 exists (e.g., 'Checklist Format')
                'name' => 'General Information',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'format_id' => 1, // Assuming Format ID 1 exists (e.g., 'Checklist Format')
                'name' => 'Safety Measures',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'format_id' => 2, // Assuming Format ID 2 exists (e.g., 'Survey Format')
                'name' => 'Customer Feedback',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}

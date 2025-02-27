<?php

namespace Database\Seeders\Clients;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProcessesTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('processes')->insert([
            [
                'name' => 'Daily Sales Report',
                'frequency_id' => 3, // Monthly
                'format_id' => 1, // PDF
                'hierarchy_id' => 1, // Headquarters
                'specific_days' => json_encode(['Monday']),
                'exclude_sundays' => true,
                'exclude_public_holidays' => true,
                'deadline' => now()->addDays(10), // Example deadline: 10 days from now
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Weekly Inventory Check',
                'frequency_id' => 2, // Weekly
                'format_id' => 2, // Excel
                'hierarchy_id' => 2, // Regional Office
                'specific_days' => json_encode(['Friday']),
                'exclude_sundays' => false,
                'exclude_public_holidays' => true,
                'deadline' => now()->addDays(5), // Example deadline: 5 days from now
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}

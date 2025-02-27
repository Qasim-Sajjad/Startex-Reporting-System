<?php

namespace Database\Seeders\Clients;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProcessesTableSeeder extends Seeder
{
    public function run()
    {
        $now = Carbon::now();
        
        DB::table('processes')->insert([
            [
                'name' => 'Daily Sales Report',
                'frequency_id' => 1, // Daily
                'hierarchynames_id' => 1,
                'specific_days' => json_encode(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']), // Including Sunday for testing
                'exclude_sundays' => false, // Changed to false for testing
                'process_deadline' => $now->copy()->addMonths(3),
                'submission_deadline' => '23:59:59',  // Extended deadline for testing
                'submission_start_time' => '00:00:00', // Start from beginning of day
                'grace_period_minutes' => 30,
                'start_date' => $now->copy()->subDays(1), // Started yesterday
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Weekly Store Inspection',
                'frequency_id' => 2, // Weekly
                'hierarchynames_id' => 1,
                'specific_days' => json_encode(['Sunday', 'Monday']), // Including Sunday for testing
                'exclude_sundays' => false, // Changed to false for testing
                'process_deadline' => $now->copy()->addMonths(6),
                'submission_deadline' => '23:59:59',
                'submission_start_time' => '00:00:00',
                'grace_period_minutes' => 60,
                'start_date' => $now->copy()->subDays(1),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Monthly Performance Review',
                'frequency_id' => 3, // Monthly
                'hierarchynames_id' => 1,
                'specific_days' => json_encode([
                    $now->copy()->format('j'), // Current day of month
                    $now->copy()->addDays(1)->format('j'), // Tomorrow's day of month
                ]),
                'exclude_sundays' => false,
                'process_deadline' => $now->copy()->addYear(),
                'submission_deadline' => '23:59:59',
                'submission_start_time' => '00:00:00',
                'grace_period_minutes' => 1440,
                'start_date' => $now->copy()->subDays(1),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            // Additional test process for immediate visibility
            [
                'name' => 'Immediate Test Checklist',
                'frequency_id' => 1, // Daily
                'hierarchynames_id' => 1,
                'specific_days' => null, // No specific days - runs every day
                'exclude_sundays' => false,
                'process_deadline' => $now->copy()->addMonths(1),
                'submission_deadline' => '23:59:59',
                'submission_start_time' => '00:00:00',
                'grace_period_minutes' => 1440, // Full day grace period
                'start_date' => $now->copy()->subDays(1),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}

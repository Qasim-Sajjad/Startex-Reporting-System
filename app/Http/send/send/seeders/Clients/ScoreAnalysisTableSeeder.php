<?php

namespace Database\Seeders\Clients;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ScoreAnalysisTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('score_analysis')->insert([
            [
               
                'q_option_id' => 1,
                'hierarchy_id' => 1, 
                'response' => 'Great experience overall.',
                'comment' => 'Keep up the good work.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
               
                'q_option_id' => 2,
                'hierarchy_id' => 2,
                'response' => 'Good but can improve.',
                'comment' => 'Consider speeding up the process.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}

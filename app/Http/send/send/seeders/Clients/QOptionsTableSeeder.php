<?php

namespace Database\Seeders\Clients;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QOptionsTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('q_options')->insert([
            [
                'question_id' => 1, // Assuming Question ID 1 exists
                'option_id' => 1, // Assuming Option ID 1 (Excellent) exists
                'score' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question_id' => 1,
                'option_id' => 2, // Assuming Option ID 2 (Good) exists
                'score' => 7,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question_id' => 1,
                'option_id' => 3, // Assuming Option ID 3 (Average) exists
                'score' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question_id' => 1,
                'option_id' => 4, // Assuming Option ID 4 (Poor) exists
                'score' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}

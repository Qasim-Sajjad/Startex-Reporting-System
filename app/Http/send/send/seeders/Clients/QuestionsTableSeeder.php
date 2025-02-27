<?php

namespace Database\Seeders\Clients;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuestionsTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('questions')->insert([
            [
                'section_id' => 1, 
                'text' => 'What is your feedback on the product?',
                'comment' => true,
                'required' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'section_id' => 1,
                'text' => 'How satisfied are you with the service?',
                'comment' => false,
                'required' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}

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
                'guidelines' => 'Please provide detailed feedback about the product.',
                'comment' => true,
                'required' => true,
                'order_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'section_id' => 1,
                'text' => 'How satisfied are you with the service?',
                'guidelines' => 'Rate your satisfaction on a scale of 1 to 5.',
                'comment' => false,
                'required' => true,
                'order_by' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        
    }
}

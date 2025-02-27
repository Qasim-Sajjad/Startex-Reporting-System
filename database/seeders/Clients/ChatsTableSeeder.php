<?php

namespace Database\Seeders\Clients;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChatsTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('chats')->insert([
            [
                'task_id' => 1, // Assuming Task ID 1 exists
                'sender_id' => 1, // User ID 1 sending the message
                'message' => 'Please update the report by Friday.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
       
        ]);
    }
}

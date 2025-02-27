<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Client;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Insert a new client into the clients table
        Client::create([
            
            'name' => 'Client ABC',
            'industry' => 'Finance',
            'email' => 'ad@clientabc.com',
            'password' => Hash::make('securepassword'),
            'address' => '123 Main Street, City',
            'database_name' => 'client_1',
            'user_id' => '1'
        ]);
    }
}

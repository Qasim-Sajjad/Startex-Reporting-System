<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class ClientDatabaseManager
{
    /**
     * Create a new database for the client.
     *
     * @param string $databaseName
     * @return void
     */
    public static function createDatabase(string $databaseName)
    {
        DB::statement("CREATE DATABASE IF NOT EXISTS {$databaseName}");
     
    }

    /**
     * Set the database connection dynamically.
     *
     * @param string $databaseName
     * @return void
     */
    public static function setConnection(string $databaseName)
    {
     //dd($databaseName);
        Config::set('database.connections.client', [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => $databaseName,
            'username' => 'root',
            'password' => '',
        ]);

        DB::purge('client'); 
        DB::reconnect('client'); 

        \Log::info('Client Database Connection Set:', [
            'connection_name' => DB::connection('client')->getName(),
            'database' => DB::connection('client')->getDatabaseName(),
            'host' => Config::get('database.connections.client.host'),
            'username' => Config::get('database.connections.client.username'),
        ]);
    }

    /**
     * Get the client-specific database connection.
     *
     * @return \Illuminate\Database\Connection
     */
    // public static function getConnection()
    // {
    //     return DB::connection('client');
    // }

    /**
     * Run client-specific migrations.
     *
     * @return void
     */
    public static function runMigrations($databaseName)
    {
      self::setConnection($databaseName);
        Artisan::call('migrate', [
            '--database' => 'client',
            '--path' => 'database/migrations/clients',
            '--force' => true,
        ]);
        
    }
    public static function seedClientDatabase($databaseName)
    {
        // Set the client database connection
        self::setConnection($databaseName);

        // Run the seeders for the client-specific database
        Artisan::call('db:seed', [
            '--class' => 'Database\\Seeders\\Clients\\ClientDatabaseSeeder',
            '--database' => 'client',

        ]);
    }

}

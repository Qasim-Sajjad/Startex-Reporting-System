<?php

namespace Database\Seeders\Clients;

use Illuminate\Database\Seeder;

class ClientDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds for client-specific tables.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            DepartmentsTableSeeder::class,
            UsersTableSeeder::class,
            HierarchynamesTableSeeder::class,
            HierarchylevelsTableSeeder::class,
            LocationsTableSeeder::class,
            HierarchiesTableSeeder::class,
            FrequenciesTableSeeder::class,
            FormatsTableSeeder::class,
            ProcessesTableSeeder::class,
            SectionsTableSeeder::class,
            QuestionsTableSeeder::class,
            OptionsTableSeeder::class,
            QOptionsTableSeeder::class,
            ScoreAnalysisTableSeeder::class,
            TasksTableSeeder::class,
            UsersTasksTableSeeder::class,
            ChatsTableSeeder::class,

            // TasksTableSeeder::class,
        ]);
    }
}

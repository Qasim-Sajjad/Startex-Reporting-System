<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('user_tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_id'); // Task ID
            $table->unsignedBigInteger('client_dbusers_id'); // User ID
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('last_reminder_sent')->nullable();
            $table->timestamps();
    
            $table->foreign('task_id')->references('id')->on('tasks')->onDelete('cascade');
            $table->foreign('client_dbusers_id')->references('id')->on('client_dbusers')->onDelete('cascade');
    
            $table->unique(['task_id', 'client_dbusers_id']);
        });
    }
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_tasks');
    }
};

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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id')->nullable(); // Client reference
            $table->unsignedBigInteger('client_dbusers_id')->nullable();
            $table->text('description');
            $table->enum('status', ['Open', 'In Progress', 'Closed']);
            $table->enum('priority', ['Low', 'Medium', 'High']);
            $table->boolean('time_bound')->default(false);

            $table->unsignedBigInteger('process_id')->nullable();
            $table->unsignedBigInteger('question_id')->nullable();
            $table->unsignedBigInteger('section_id')->nullable();
            $table->unsignedBigInteger('format_id')->nullable();


            $table->unsignedBigInteger('department_id');
            $table->timestamp('deadline')->nullable(); // Add deadline field
            $table->timestamp('closed_on')->nullable();
            $table->timestamps();
           
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('cascade');
            $table->foreign('question_id')->references('id')->on('questions')->onDelete('cascade');
            $table->foreign('section_id')->references('id')->on('sections')->onDelete('cascade');
            $table->foreign('format_id')->references('id')->on('formats')->onDelete('cascade');
           

            // $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('client_dbusers_id')->references('id')->on('client_dbusers')->onDelete('cascade');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
        });
    }
    
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};

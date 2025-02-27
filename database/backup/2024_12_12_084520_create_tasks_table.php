<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('client_id'); 
            $table->text('description'); 
            $table->enum('status', ['Open', 'In Progress', 'Closed']);
            $table->enum('priority', ['Low', 'Medium', 'High']); 
            $table->boolean('time_bound')->default(false); 
            $table->unsignedBigInteger('department_id'); 
            $table->timestamp('closed_on')->nullable();
            $table->timestamps(); 

          
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks'); // Drop the table
    }
};

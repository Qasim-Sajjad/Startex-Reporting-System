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
        Schema::create('formats', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('process_id'); // Add a nullable process_id column
            $table->timestamps();
    
            // Foreign key for process_id referencing processes table
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('cascade'); // Use 'set null' in case process is deleted
        });
    }
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('formats');
    }
};

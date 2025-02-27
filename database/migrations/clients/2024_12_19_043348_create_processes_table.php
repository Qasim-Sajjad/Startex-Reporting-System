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
        Schema::create('processes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('frequency_id')->nullable();
         
            $table->date('start_date')->nullable();
            $table->unsignedBigInteger('hierarchynames_id')->nullable();
            $table->time('submission_deadline')->nullable();    // e.g., "17:00:00"
            $table->time('submission_start_time')->nullable();  // e.g., "09:00:00"
            $table->integer('grace_period_minutes')->default(0);
            $table->json('specific_days')->nullable();
            $table->boolean('exclude_sundays')->default(false);
            $table->boolean('exclude_public_holidays')->default(false);
            $table->timestamp('process_deadline')->nullable(); // Add deadline field
            $table->timestamps();
    
            $table->foreign('frequency_id')->references('id')->on('frequencies')->onDelete('cascade');
       

            $table->foreign('hierarchynames_id')->references('id')->on('hierarchynames')->onDelete('cascade');
        });
    }
    
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('processes');
    }
};

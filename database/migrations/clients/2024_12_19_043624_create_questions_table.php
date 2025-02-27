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
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('section_id');
            $table->text('text')->nullable();
          $table->text('type')->nullable();
            $table->integer('tscore')->nullable(); // Optional guidelines field
                      $table->text('guidelines')->nullable(); // Optional guidelines field
            $table->boolean('comment')->default(false);
            $table->boolean('required')->default(false);
            $table->integer('order_by')->default(1); // Default order
            $table->timestamps();
    
            $table->foreign('section_id')->references('id')->on('sections')->onDelete('cascade');
        });
    }
    
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};

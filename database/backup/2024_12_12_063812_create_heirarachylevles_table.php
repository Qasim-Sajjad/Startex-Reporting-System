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

        Schema::create('hierarchylevels', function (Blueprint $table) {
            $table->id();
            $table->string('name'); 
            $table->integer('level'); 
            $table->unsignedBigInteger('hierarchynames_id'); 

            $table->timestamps();
            $table->foreign('hierarchynames_id')->references('id')->on('hierarchynames')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hierarchylevels');
    }
};

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
        Schema::create('hierarchies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('location_id');
            $table->unsignedBigInteger('hierarchylevels_id');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedBigInteger('client_dbusers_id')->nullable(); 
            $table->string('branch_code')->nullable();
            $table->string('address')->nullable();
            $table->timestamps();
        
            $table->foreign('hierarchylevels_id')->references('id')->on('hierarchylevels')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('cascade');
            $table->foreign('client_dbusers_id')->references('id')->on('client_dbusers')->onDelete('set null');
        });
        
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hierarchies');
    }
};

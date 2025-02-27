<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->enum('role', ['Client Admin']);
            $table->string('name'); 
            $table->string('email')->unique();
            $table->string('industry');
            $table->string('address');  // No default value
            $table->string('password'); // No default value
            $table->string('database_name')->nullable(); // This will be set later
            $table->unsignedBigInteger('user_id'); 
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->timestamps();
    
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
    

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('clients');
    }
};

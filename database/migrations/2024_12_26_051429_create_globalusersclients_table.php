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
        Schema::create('globalusersclients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id'); // Foreign key to the clients table
            $table->enum('role', ['User', 'Client Admin', 'EndUser']); // Added EndUser
            $table->string('email')->unique();
            $table->string('password');
            $table->string('database_name');
            $table->timestamps();

            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('globalusersclients');
    }
};

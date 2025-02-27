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
        Schema::create('client_dbusers', function (Blueprint $table) {
        $table->id();
        $table->string('name'); // User name
        $table->string('email')->unique(); // User email
        $table->string('password'); // Password (hashed)
        $table->enum('role', ['User'])->default('User'); // Role
        $table->unsignedBigInteger('department_id')->nullable(); // Linked department
        $table->timestamps();

        $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
    }); 
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_dbusers');
    }
};

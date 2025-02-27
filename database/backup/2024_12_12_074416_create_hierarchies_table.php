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
        Schema::create('hierarchies', function (Blueprint $table) {
            $table->id(); // Primary Key
            $table->unsignedBigInteger('location_id'); // Foreign Key: Location
            $table->unsignedBigInteger('hierarchylevels_id'); // Foreign Key: Hierarchy Levels
            $table->unsignedBigInteger('parent_id')->nullable(); // Parent branch (nullable for top-level branches)
            $table->string('branch_code')->nullable(); // Unique code for the branch
            $table->string('address')->nullable(); // Address of the branch
            $table->timestamps(); // Created_at and updated_at

            // Foreign Key Constraints
            $table->foreign('hierarchylevels_id')->references('id')->on('hierarchylevels')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hierarchies'); // Drop the table
    }
};

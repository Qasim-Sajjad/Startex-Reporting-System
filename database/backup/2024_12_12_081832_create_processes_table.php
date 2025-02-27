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
        Schema::create('processes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('frequency_id');
            $table->unsignedBigInteger('format_id');
            $table->unsignedBigInteger('hierarchy_id');
            $table->json('specific_days')->nullable();
            $table->boolean('exclude_sundays')->default(false);
            $table->boolean('exclude_public_holidays')->default(false);
            $table->timestamps();
        
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('frequency_id')->references('id')->on('frequencies')->onDelete('cascade');
            $table->foreign('format_id')->references('id')->on('formats')->onDelete('cascade');
            $table->foreign('hierarchy_id')->references('id')->on('hierarchies')->onDelete('cascade');
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

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
        Schema::create('attachment_entities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('attachment_id');
            $table->unsignedBigInteger('entity_id'); // ID of the task, section, question, or process
            $table->string('entity_type'); // The entity type (Task, Section, etc.)
            $table->timestamps();
        
            $table->foreign('attachment_id')->references('id')->on('attachments')->onDelete('cascade');
            $table->index(['entity_id', 'entity_type']); // Index for faster lookups
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attachment_entities');
    }
};

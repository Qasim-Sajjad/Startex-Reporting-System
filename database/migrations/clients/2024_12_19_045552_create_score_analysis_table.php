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
        Schema::create('score_analysis', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('q_option_id');
            $table->unsignedBigInteger('hierarchy_id');
            $table->unsignedBigInteger('format_id');
            $table->string('response');
            $table->text('comment')->nullable();
            $table->timestamps();
    
            $table->foreign('q_option_id')->references('id')->on('q_options')->onDelete('cascade');
            $table->foreign('hierarchy_id')->references('id')->on('hierarchies')->onDelete('cascade');
            $table->foreign('format_id')->references('id')->on('formats')->onDelete('cascade');
        });
    }
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('score_analysis');
    }
};

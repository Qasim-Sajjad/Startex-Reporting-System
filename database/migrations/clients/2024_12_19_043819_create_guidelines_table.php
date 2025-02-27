<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('guidelines', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('guideline_type')->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('entity_type')->nullable();
            $table->string('filepath');
            $table->string('drive_link')->nullable();
            $table->unsignedBigInteger('uploaded_by');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('guidelines');
    }
};
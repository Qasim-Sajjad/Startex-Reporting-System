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
    Schema::create('chats', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('task_id');
        $table->unsignedBigInteger('sender_id');
        $table->text('message');
        $table->timestamps();

        $table->foreign('task_id')->references('id')->on('tasks')->onDelete('cascade');
        $table->foreign('sender_id')->references('id')->on('users')->onDelete('cascade');
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chats');
    }
};

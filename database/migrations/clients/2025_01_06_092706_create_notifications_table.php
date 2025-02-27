<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationsTable extends Migration
{
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_dbusers_id'); 
            $table->string('type'); 
            $table->string('message');
            $table->boolean('is_read')->default(false);
            $table->timestamps();

            $table->foreign('client_dbusers_id')->references('id')->on('client_dbusers')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('notifications');
    }
}

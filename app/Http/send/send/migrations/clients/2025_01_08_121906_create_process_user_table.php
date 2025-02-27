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
        Schema::create('process_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('process_id'); 
            $table->unsignedBigInteger('client_dbuser_id'); 
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();

            $table->foreign('process_id')->references('id')->on('processes')->onDelete('cascade');
            $table->foreign('client_dbuser_id')->references('id')->on('client_dbusers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('process_user');
    }
};

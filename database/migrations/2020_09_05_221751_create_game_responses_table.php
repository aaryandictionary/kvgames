<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGameResponsesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('game_responses', function (Blueprint $table) {
            $table->bigIncrements('response_id');
            $table->bigInteger('user_id');
            $table->string('firebase_uid');
            $table->bigInteger('ticket_id');
            $table->bigInteger('join_id');
            $table->double('win_amount');
            $table->string('ticket_type');//For Early Five Lines and Full House
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('game_responses');
    }
}

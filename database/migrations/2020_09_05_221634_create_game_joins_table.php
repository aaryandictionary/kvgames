<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGameJoinsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('game_joins', function (Blueprint $table) {
            $table->bigIncrements('join_id');
            $table->string('firebase_uid');
            $table->bigInteger('user_id');
            $table->date('game_date');
            $table->time('game_time');
            $table->time('join_time');
            $table->integer('ticket_count');
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
        Schema::dropIfExists('game_joins');
    }
}

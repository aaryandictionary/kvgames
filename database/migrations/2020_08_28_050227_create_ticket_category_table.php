<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketCategoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ticket_category', function (Blueprint $table) {
            $table->bigIncrements('ticket_category_id');
            $table->time('ticket_time');
            $table->double('tup_1');
            $table->double('tup_2');
            $table->double('tup_3');
            $table->double('tup_4');
            $table->integer('is_enabled');
            $table->integer('double_game')->default(0);
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
        Schema::dropIfExists('ticket_category');
    }
}

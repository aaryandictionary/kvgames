<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMyTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('my_tickets', function (Blueprint $table) {
            $table->bigIncrements('my_ticket_id');
            $table->bigInteger('user_id');
            $table->date('my_ticket_date');
            $table->time('my_ticket_time');
            $table->text('ticket_combo');
            $table->double('ticket_unit_price');
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
        Schema::dropIfExists('my_tickets');
    }
}

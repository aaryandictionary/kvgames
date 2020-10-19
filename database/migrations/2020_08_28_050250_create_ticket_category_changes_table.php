<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketCategoryChangesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ticket_category_changes', function (Blueprint $table) {
            $table->bigIncrements('tc_change_id');
            $table->double('tup_1');
            $table->double('tup_2');
            $table->double('tup_3');
            $table->double('tup_4');
            $table->date('change_for_date')->nullable();
            $table->time('change_for_time')->nullable();
            $table->integer('status');
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
        Schema::dropIfExists('ticket_category_changes');
    }
}

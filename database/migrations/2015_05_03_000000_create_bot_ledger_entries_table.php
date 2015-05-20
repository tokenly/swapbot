<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBotLedgerEntriesTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bot_ledger_entries', function(Blueprint $table)
        {
            $table->increments('id');
            $table->char('uuid', 36)->unique();

            $table->integer('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users');

            $table->integer('bot_id')->unsigned();
            $table->foreign('bot_id')->references('id')->on('bots');

            $table->integer('bot_event_id')->unsigned();
            $table->foreign('bot_event_id')->references('id')->on('bot_events');

            $table->boolean('is_credit')->default(0);
            $table->bigInteger('amount')->unsigned()->default(0);  // 0.0001

            $table->timestamp('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('bot_ledger_entries');
    }

}

<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function(Blueprint $table)
        {
            $table->increments('id');

            $table->char('txid', 64)->index();
            $table->longText('xchain_notification')->nullable();
            $table->string('type')->default('receive');

            $table->integer('confirmations')->unsigned()->default(0);
            $table->boolean('processed')->default(false);
            $table->boolean('balances_applied')->default(false);

            $table->integer('billed_event_id')->unsigned()->nullable();
            $table->foreign('billed_event_id')->references('id')->on('bot_events');

            $table->integer('bot_id')->unsigned()->index();
            $table->foreign('bot_id')->references('id')->on('bots');

            $table->unique(['txid', 'bot_id', 'type']);

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
        Schema::drop('transactions');
    }

}

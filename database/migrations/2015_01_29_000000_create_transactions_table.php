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

            $table->integer('confirmations')->unsigned()->default(0);
            $table->boolean('processed')->default(false);
            $table->char('processed_txid', 64)->nullable();

            $table->integer('bot_id')->unsigned()->index();
            $table->foreign('bot_id')->references('id')->on('bots');

            $table->index(['txid', 'bot_id']);

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

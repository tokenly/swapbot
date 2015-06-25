<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBotLeaseEntriesTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bot_lease_entries', function(Blueprint $table)
        {
            $table->increments('id');
            $table->char('uuid', 36)->unique();

            $table->integer('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users');

            $table->integer('bot_id')->unsigned();
            $table->foreign('bot_id')->references('id')->on('bots');

            $table->integer('bot_event_id')->unsigned();
            $table->foreign('bot_event_id')->references('id')->on('bot_events');

            $table->timestamp('start_date')->index();
            $table->timestamp('end_date')->index();

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
        Schema::drop('bot_lease_entries');
    }

}

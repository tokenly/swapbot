<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBotEventsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('bot_events', function(Blueprint $table)
		{
			$table->increments('id');

            $table->char('uuid', 36)->unique();

            $table->integer('bot_id')->unsigned();
            $table->foreign('bot_id')->references('id')->on('bots');

            $table->integer('swap_id')->unsigned()->nullable();
            $table->foreign('swap_id')->references('id')->on('swaps');

            $table->mediumInteger('level')->unsigned();
            $table->longText('event');

			$table->timestamp('created_at');
			$table->bigInteger('serial')->unsigned()->index();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('bot_events');
	}

}

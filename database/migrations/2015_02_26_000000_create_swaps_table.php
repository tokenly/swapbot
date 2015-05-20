<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSwapsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('swaps', function(Blueprint $table)
        {
            $table->increments('id');
            $table->char('uuid', 36)->unique();

            $table->string('name');

            $table->integer('bot_id')->unsigned()->index();
            $table->foreign('bot_id')->references('id')->on('bots');

            $table->integer('transaction_id')->unsigned()->index();

            $table->string('state');
            $table->mediumText('definition')->nullable();

            $table->mediumText('receipt')->nullable();

            $table->timestamp('completed_at')->nullable();

            $table->unique(['bot_id', 'transaction_id', 'name', ]);
            
            $table->index('created_at');
            $table->index('completed_at');

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
        Schema::drop('swaps');
    }

}

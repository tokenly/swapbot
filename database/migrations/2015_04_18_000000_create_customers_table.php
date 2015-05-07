<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCustomersTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customers', function(Blueprint $table)
        {
            $table->increments('id');
            $table->char('uuid', 36)->unique();
            $table->string('email');

            $table->integer('swap_id')->unsigned()->index();
            $table->foreign('swap_id')->references('id')->on('swaps');

            $table->boolean('active')->default(1);

            $table->unique(['email', 'swap_id', ]);

            $table->char('unsubscribe_token', 24)->nullable();

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
        Schema::drop('customers');
    }

}

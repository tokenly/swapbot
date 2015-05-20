<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSwapsForeignKeys extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('swaps', function(Blueprint $table)
        {
            $table->foreign('transaction_id')->references('id')->on('transactions');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('swaps', function(Blueprint $table)
        {
            $table->dropForeign('swaps_transaction_id_foreign');
        });
    }

}

<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBotLedgerEntriesAssets extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bot_ledger_entries', function(Blueprint $table)
        {
            $table->string('asset')->default('BTC')->index();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bot_ledger_entries', function(Blueprint $table)
        {
            $table->dropColumn('asset');
        });
    }

}

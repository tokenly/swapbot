<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSwapRulesToBots extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bots', function (Blueprint $table) {
            $table->string('swap_rules')->nullable()->unique();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bots', function (Blueprint $table) {
            $table->dropColumn('swap_rules');
        });
    }
}

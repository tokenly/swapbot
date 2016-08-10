<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddWhitelistedToSwapIndexTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('swap_index', function (Blueprint $table) {
            $table->boolean('whitelisted')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('swap_index', function (Blueprint $table) {
            $table->dropColumn('whitelisted');
        });
    }
}

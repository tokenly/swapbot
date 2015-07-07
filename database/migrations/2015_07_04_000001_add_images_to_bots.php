<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddImagesToBots extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bots', function (Blueprint $table) {
            $table->integer('background_image_id')->unsigned()->nullable();
            $table->integer('logo_image_id')->unsigned()->nullable();
            $table->text('background_overlay_settings')->nullable();
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
            $table->dropColumn('background_overlay_settings');
            $table->dropColumn('background_image_id');
            $table->dropColumn('logo_image_id');
        });
    }
}

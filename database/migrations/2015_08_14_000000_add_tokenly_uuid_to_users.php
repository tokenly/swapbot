<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTokenlyUuidToUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->char('tokenly_uuid', 36)->nullable()->unique();
            $table->char('oauth_token', 40)->nullable()->unique();
            $table->boolean('email_is_confirmed')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('tokenly_uuid');
            $table->dropColumn('oauth_token');
            $table->dropColumn('email_is_confirmed');
        });
    }
}

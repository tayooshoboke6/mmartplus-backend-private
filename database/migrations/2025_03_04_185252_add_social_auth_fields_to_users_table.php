<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('social_id')->nullable()->after('password');
            $table->string('social_type')->nullable()->after('social_id');
            $table->string('avatar')->nullable()->after('social_type');
            // Make password nullable since social auth users may not have a password initially
            $table->string('password')->nullable()->change();
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
            $table->dropColumn(['social_id', 'social_type', 'avatar']);
            // Revert password to be required
            $table->string('password')->nullable(false)->change();
        });
    }
};

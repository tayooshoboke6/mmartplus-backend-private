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
        Schema::table('products', function (Blueprint $table) {
            // Add short description field
            if (!Schema::hasColumn('products', 'short_description')) {
                $table->string('short_description')->nullable()->after('description');
            }

            // Add is_featured field
            if (!Schema::hasColumn('products', 'is_featured')) {
                $table->boolean('is_featured')->default(false)->after('is_active');
            }

            // Add expiry_date field
            if (!Schema::hasColumn('products', 'expiry_date')) {
                $table->date('expiry_date')->nullable()->after('category_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            // Drop short_description field
            if (Schema::hasColumn('products', 'short_description')) {
                $table->dropColumn('short_description');
            }

            // Drop is_featured field
            if (Schema::hasColumn('products', 'is_featured')) {
                $table->dropColumn('is_featured');
            }

            // Drop expiry_date field
            if (Schema::hasColumn('products', 'expiry_date')) {
                $table->dropColumn('expiry_date');
            }
        });
    }
};

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
        // Check if slug column already exists
        if (!Schema::hasColumn('products', 'slug')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('slug')->nullable()->after('name');
            });

            // Generate slugs for existing products
            $products = \App\Models\Product::all();
            foreach ($products as $product) {
                $product->slug = \Illuminate\Support\Str::slug($product->name);
                $product->save();
            }

            // Now add the not nullable constraint
            Schema::table('products', function (Blueprint $table) {
                $table->string('slug')->nullable(false)->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('products', 'slug')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('slug');
            });
        }
    }
};

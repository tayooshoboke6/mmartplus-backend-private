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
            $table->decimal('average_rating', 3, 2)->default(4.0)->after('delivery_time');
            $table->integer('rating_count')->default(0)->after('average_rating');
        });

        // Create a new ratings table to store individual user ratings
        Schema::create('product_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('rating')->comment('Rating from 1-5');
            $table->text('review')->nullable();
            $table->boolean('verified_purchase')->default(false);
            $table->timestamps();
            
            // Each user can only rate a product once
            $table->unique(['product_id', 'user_id']);
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
            $table->dropColumn('average_rating');
            $table->dropColumn('rating_count');
        });

        Schema::dropIfExists('product_ratings');
    }
};

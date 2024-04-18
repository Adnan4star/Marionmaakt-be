<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateArticleIngredientProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('article_ingredient_products', function (Blueprint $table) {
            $table->id();
            $table->integer('article_id')->nullable();
            $table->integer('product_id')->nullable();
            $table->string('grams')->nullable();
            $table->string('percentage')->nullable();
            $table->string('phase')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('article_ingredient_products');
    }
}

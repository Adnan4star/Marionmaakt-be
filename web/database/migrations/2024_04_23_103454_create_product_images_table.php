<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductImagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->integer('shopify_id')->nullable();
            $table->integer('product_id')->nullable();
            $table->longText('src')->nullable();
            $table->string('position')->nullable();
            $table->string('width')->nullable();
            $table->string('height')->nullable();
            $table->string('alt')->nullable();
            $table->longText('admin_graphql_api_id')->nullable();
            $table->integer('variant_ids')->nullable();
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
        Schema::dropIfExists('product_images');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductVariantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('shop_id')->nullable();
            $table->bigInteger('shopify_product_id')->nullable();
            $table->bigInteger('shopify_id')->nullable();
            $table->longText('title')->nullable();
            $table->double('price')->nullable();
            $table->double('compare_at_price')->nullable();
            $table->longText('sku')->nullable();
            $table->boolean('taxable')->nullable();
            $table->longText('inventory_management')->nullable();
            $table->bigInteger('inventory_quantity')->nullable();
            $table->longText('inventory_policy')->nullable();
            $table->double('weight')->nullable();
            $table->double('grams')->nullable();
            $table->string('weight_unit')->nullable();
            $table->bigInteger('inventory_item_id')->nullable();
            $table->longText('image')->nullable();
            $table->string('option1')->nullable();
            $table->string('option2')->nullable();
            $table->string('option3')->nullable();
            $table->boolean('requires_shipping')->nullable();
            $table->string('fulfillment_service')->nullable();
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
        Schema::dropIfExists('product_variants');
    }
}

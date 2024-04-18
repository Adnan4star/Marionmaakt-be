<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBlogArticlesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('blog_articles', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('shopify_id')->nullable();
            $table->bigInteger('shop_id')->nullable();
            $table->integer('blog_id')->nullable();
            $table->string('title')->nullable();
            $table->longText('body_html')->nullable();
            $table->longText('summary_html')->nullable();
            $table->bigInteger('shopify_blog_id')->nullable();
            $table->string('author')->nullable();
            $table->bigInteger('user_id')->nullable();
            $table->string('handle')->nullable();
            $table->longText('tags')->nullable();
            $table->longText('image')->nullable();
            $table->longText('published_at')->nullable();
            $table->longText('usage')->nullable();
            $table->longText('preparation')->nullable();
            $table->longText('total_time')->nullable();
            $table->longText('recipe_by')->nullable();
            $table->longText('level')->nullable();
            $table->longText('shelf_life')->nullable();
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
        Schema::dropIfExists('blog_articles');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlogArticle extends Model
{
    use HasFactory;
    protected $fillable = ['shop_id','shopify_id','title','body_html','summary_html','shopify_blog_id','author','user_id','handle','tags','published_at','blog_id'];

    public function ArticleIngredientProduct()
    {
        return $this->hasMany(ArticleIngredientProduct::class, 'article_id','id');
    }
    
    public function ArticleToolAccessory()
    {
        return $this->hasMany(ArticleToolAccessory::class, 'article_id','id');
    }

    public function ArticleInstructions()
    {
        return $this->hasMany(ArticleInstruction::class, 'article_id','id');
    }

    public function ArticleFilters()
    {
        return $this->hasMany(ArticleFilter::class, 'article_id','id');
    }

    public function ArticleDescriptions()
    {
        return $this->hasMany(ArticleDescription::class, 'article_id','id');
    }
}

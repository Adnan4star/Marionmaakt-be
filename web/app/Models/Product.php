<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Psy\TabCompletion\Matcher\FunctionsMatcher;

class Product extends Model
{
    use HasFactory;

    public function ProductVariant()
    {
        return $this->hasMany(ProductVariant::class, 'shopify_product_id','shopify_id');
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

}

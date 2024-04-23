<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    use HasFactory; 

    protected $fillable = ['product_id','shopify_id','alt','position','width','height','src'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

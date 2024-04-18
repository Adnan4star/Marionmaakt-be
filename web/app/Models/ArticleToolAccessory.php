<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArticleToolAccessory extends Model
{
    use HasFactory;

    public function ProductRecord(){
        return  $this->hasOne(Product::class, 'id', 'product_id');
    }
}

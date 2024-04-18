<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FilterValue extends Model
{
    use HasFactory;

    public function articleFilter()
    {
        return $this->belongsTo(ArticleFilter::class, 'filter_id');
    }
}


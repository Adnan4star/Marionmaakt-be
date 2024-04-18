<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArticleFilter extends Model
{
    use HasFactory;

    public function FilterRecord(){
        return  $this->belongsTo(Filter::class, 'id', 'filter_id');
    }

    public function FilterValueRecord(){
        return  $this->belongsTo(FilterValue::class, 'id', 'filter_value_id');
    }

    public function filterValues()
    {
        return $this->hasMany(FilterValue::class, 'id','filter_value_id');
    }


}

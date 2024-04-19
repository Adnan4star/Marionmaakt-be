<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Filter extends Model
{
    use HasFactory;

    protected $fillable = ['label', 'status', 'values'];

    public function FilterValues()
    {
        return $this->hasMany(FilterValue::class, 'filter_id','id');
    }
}

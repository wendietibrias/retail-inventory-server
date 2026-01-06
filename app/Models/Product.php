<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'products';
    protected $guarded = [];

    public function productCategory(){
        return $this->belongsTo(ProductCategory::class,'product_category_id');
    }
}

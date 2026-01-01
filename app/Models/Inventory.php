<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $table = 'inventory';

    protected $guarded = [];

    public function productSku(){
        return $this->belongsTo(ProductSku::class,'product_sku_id');
    }

    public function warehouse(){
       return $this->belongsTo(Warehouse::class,'warehouse_id');
    }
}

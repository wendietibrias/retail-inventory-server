<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockAdjustmentDetail extends Model
{
    protected $table = 'stock_adjustment_details';

    protected $guarded=[];

    public function productSku(){
        return $this->belongsTo(ProductSku::class,'product_sku_id');
    }

    public function stockAdjustment(){
        return $this->belongsTo(StockAdjustment::class,'stock_adjustment_id');
    }
}

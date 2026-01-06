<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockAdjustment extends Model
{
    protected $table  = 'stock_adjustments';
    protected $guarded = [];

    public function stockAdjustmentDetails(){
        return $this->hasMany(StockAdjustmentDetail::class,'stock_adjustment_id');
    }

    public function warehouse(){
        return $this->belongsTo(Warehouse::class,'warehouse_id');
    }
}

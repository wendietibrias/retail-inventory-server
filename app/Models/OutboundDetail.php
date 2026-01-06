<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OutboundDetail extends Model
{
        protected $table = "outbound_details";
    protected $guarded =[];

    public function productSku(){
        return $this->belongsTo(ProductSku::class,'product_sku_id');
    }

    public function outbound(){
        return $this->belongsTo(Outbound::class,'outbound_id');
    }
}

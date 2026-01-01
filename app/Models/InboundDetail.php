<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InboundDetail extends Model
{
    protected $table = "inbound_details";
    protected $guarded =[];

    public function productSku(){
        return $this->belongsTo(ProductSku::class,'product_sku_id');
    }

    public function inbound(){
        return $this->belongsTo(Inbound::class,'inbound_id');
    }
}

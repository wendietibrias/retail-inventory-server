<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MutationOutDetail extends Model
{
    protected $table = 'mutation_out_details';

    protected $guarded = [];

    public function productSku(){
        return $this->belongsTo(ProductSku::class,'product_sku_id');
    }

    public function mutationOut(){
        return $this->belongsTo(MutationOut::class,'mutation_out_id');
    }
}

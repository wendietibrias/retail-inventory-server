<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MutationInDetail extends Model
{
    protected $table = 'mutation_in_details';

    protected $guarded = [];

        public function productSku(){
        return $this->belongsTo(ProductSku::class,'product_sku_id');
    }

    public function mutationOut(){
        return $this->belongsTo(MutationIn::class,'mutation_in_id');
    }

    
}

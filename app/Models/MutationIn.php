<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MutationIn extends Model
{
    protected $table = 'mutation_in';

    protected $guarded = [];

           public function toWarehouse(){
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    
    public function fromWarehouse(){
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function mutationInDetails(){
        return $this->hasMany(MutationInDetail::class,'mutation_in_id');
    }
}

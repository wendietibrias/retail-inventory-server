<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MutationOut extends Model
{
    protected $table = 'mutation_out';

    protected $guarded = [];

    public function toWarehouse(){
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    
    public function fromWarehouse(){
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function mutationOutDetails(){
        return $this->hasMany(MutationOutDetail::class,'mutation_out_id');
    }
}

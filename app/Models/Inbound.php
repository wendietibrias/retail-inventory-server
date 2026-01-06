<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inbound extends Model
{
    protected $table = "inbounds";

    protected $guarded = [];

    public function inboundDetails(){
        return $this->hasMany(InboundDetail::class,'inbound_id');
    }

    public function warehouse(){
        return $this->belongsTo(Warehouse::class,'warehouse_id');
    }

    public function supplier(){
        return $this->belongsTo(Supplier::class,'supplier_id');
    }

    public function createdBy(){
        return $this->belongsTo(User::class,'created_by_id');
    }
}

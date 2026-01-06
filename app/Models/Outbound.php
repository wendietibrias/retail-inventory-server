<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Outbound extends Model
{
        protected $table = "outbounds";
    protected $guarded =[];

    public function warehouse(){
        return $this->belongsTo(Warehouse::class,'warehouse_id');
    }

    public function customer(){
        return $this->belongsTo(Customer::class,'customer_id');
    }

    public function outboundDetails(){
        return $this->hasMany(OutboundDetail::class,'outbound_id');
    }

    public function createdBy(){
        return $this->belongsTo(User::class,'created_by_id');
    }
}

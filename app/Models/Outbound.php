<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Outbound extends Model
{
        protected $table = "outbound";
    protected $guarded =[];

    public function customer(){
        return $this->belongsTo(Customer::class,'customer_id');
    }

    public function outboundDetails(){
        return $this->hasMany(Outbound::class,'outbound_id');
    }
}

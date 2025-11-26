<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayableDetail extends Model
{
    protected $table = 'payable_details';
    
    public function payable(){
        return $this->belongsTo(Payable::class,'payable_id');
    }
}

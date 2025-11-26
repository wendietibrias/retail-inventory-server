<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payable extends Model
{
    protected $table = 'payables';

    protected $guarded = [];

    public function payableDetails(){
        return $this->hasMany(PayableDetail::class,'payable_id');
    }

    public function createdBy(){
        return $this->belongsTo(User::class,'user_id');
    }
}

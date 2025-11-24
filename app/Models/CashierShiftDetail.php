<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashierShiftDetail extends Model
{
    protected $table = 'cashier_shift_details';

    public function shiftTransactions(){
        return $this->hasMany(ShiftTransaction::class,'cashier_shift_detail_id');
    }

    public function cashier(){
        return $this->belongsTo(User::class,'cashier_id');
    }
}

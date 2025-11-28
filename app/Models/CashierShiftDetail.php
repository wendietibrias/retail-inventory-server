<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashierShiftDetail extends Model
{
    protected $table = 'cashier_shift_details';

    protected $guarded = [];

    public function transactionSummarizeDetail(){
        return $this->hasOne(CashierShiftDetail::class,'cs_detail_id');
    }

    public function shiftTransactions(){
        return $this->hasMany(ShiftTransaction::class,'cs_detail_id');
    }

    public function operationalCosts(){
        return $this->hasMany(OperationalCost::class,'cashier_shift_detail_id');
    }

    public function cashier(){
        return $this->belongsTo(User::class,'cashier_id');
    }
}

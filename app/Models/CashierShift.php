<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashierShift extends Model
{
    protected $table = 'cashier_shifts';

    public function cashierShiftDetails(){
        return $this->hasMany(CashierShiftDetail::class,'cashier_shift_id');
    }
}

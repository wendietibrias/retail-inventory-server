<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OperationalCost extends Model
{
    protected $table = 'operational_costs';

    protected $guarded = [];

    public function cashierShiftDetail(){
        return $this->belongsTo(CashierShiftDetail::class,'cashier_shift_detail_id');
    }
}

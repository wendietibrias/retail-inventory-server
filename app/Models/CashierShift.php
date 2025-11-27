<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashierShift extends Model
{
    protected $table = 'cashier_shifts';


    protected $fillable = [
        'code',
        'description',
        'created_by_id',
        'whole_total_sales',
        'total_cash_in_box',
        'total_cash_drawer',
        'total_difference'
    ];

    public function cashierShiftDetails(){
        return $this->hasMany(CashierShiftDetail::class,'cashier_shift_id');
    }

    public function createdBy(){
    return $this->belongsTo(User::class,'created_by_id');
    }

    public function transactionSummarize(){
        return $this->hasOne(TransactionSummarize::class,'cashier_shift_id');
    }
}

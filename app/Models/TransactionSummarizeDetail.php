<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionSummarizeDetail extends Model
{
    protected $table = 'transaction_summarize_details';

    protected $guarded = [];

    public function cashierShiftDetail(){
        return $this->belongsTo(CashierShiftDetail::class,'cs_detail_id');
    }

    public function transactionSummarizeDetailPayment(){
        return $this->hasMany(TransactionSummarizeDetailpayment::class,'tsd_id');
    }
}

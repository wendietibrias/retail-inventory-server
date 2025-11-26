<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionSummarizeDetail extends Model
{
    protected $table = 'transaction_summarize_details';

    protected $guarded = [];

    public function transactionSummarizeDetailPayment(){
        return $this->hasMany(TransactionSummarizeDetailpayment::class,'tsd_id');
    }
}

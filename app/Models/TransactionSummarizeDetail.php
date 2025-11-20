<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionSummarizeDetail extends Model
{
    protected $table = 'transaction_summarize_details';

    public function transactionSummarizeDetailPayment(){
        return $this->hasMany(TransactionSummarizeDetailpayment::class,'transaction_summarize_detail_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionSummarize extends Model
{
    protected $table = 'transaction_summarize';

    public function transactionSummarizeDetails(){
        return $this->hasMany(TransactionSummarizeDetail::class,'transaction_summarize_id');
    }
}

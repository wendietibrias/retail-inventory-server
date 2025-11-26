<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionSummarize extends Model
{
    protected $table = 'transaction_summarize';

    protected $guarded = [];

    public function transactionSummarizeDetails(){
        return $this->hasMany(TransactionSummarizeDetail::class,'ts_id');
    }
}

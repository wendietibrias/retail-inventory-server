<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Receiveable extends Model
{
    protected $table = 'receiveables';

    protected $fillable = [];

    public function salesInvoice(){
        return $this->belongsTo(SalesInvoice::class,'sales_invoice_id');
    }

    public function receiveablePayments(){
        return $this->hasMany(ReceiveablePayment::class,'receiveable_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShiftTransaction extends Model
{
    protected $table = 'shift_transactions';

    public function paymentMethodDetail()
    {
        return $this->belongsTo(PaymentType::class, 'pm_detail_id');
    }

    public function downPaymentMethodDetail()
    {
        return $this->belongsTo(PaymentType::class, 'dpm_detail_id');

    }

    public function otherPaymentMethodDetail(){
        return $this->belongsTo(PaymentType::class,'opm_detail_id');
    }
}

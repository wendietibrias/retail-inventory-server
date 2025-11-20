<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShiftTransaction extends Model
{
    protected $table = 'shift_transactions';

    public function paymentMethodDetail()
    {
        return $this->belongsTo(PaymentType::class, 'payment_method_detail_id');
    }

    public function downPaymentMethodDetail()
    {
        return $this->belongsTo(PaymentType::class, 'down_payment_method_detail_id');

    }
}

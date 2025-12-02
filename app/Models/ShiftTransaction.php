<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShiftTransaction extends Model
{
    protected $table = 'shift_transactions';

    protected $guarded = [];

    public function salesInvoice()
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }

    public function paymentMethodDetail()
    {
        return $this->belongsTo(PaymentType::class, 'pm_detail_id');
    }

    public function downPaymentMethodDetail()
    {
        return $this->belongsTo(PaymentType::class, 'dpm_detail_id');

    }

    public function otherPaymentMethodDetail()
    {
        return $this->belongsTo(PaymentType::class, 'opm_detail_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReceiveablePayment extends Model
{
    protected $table = 'receiveable_payments';
    protected $fillable = [];

    public function receiveable(){
        return $this->belongsTo(Receiveable::class,'receiveable_id');
    }

    public function paymentMethodDetail()
    {
        return $this->belongsTo(PaymentType::class, 'pm_detail_id');
    }

    public function otherPaymentMethodDetail()
    {
        return $this->belongsTo(PaymentType::class, 'opm_detail_id');
    }

    public function rejectBy()
    {
        return $this->belongsTo(User::class, 'reject_by_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function approveBy()
    {
        return $this->belongsTo(User::class, 'approve_by_id');
    }

      public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }
}

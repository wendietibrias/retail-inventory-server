<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $table = 'payment_methods';
    protected $fillable = [
        'name',
        'code',
        'description',
        'total_payment'
    ];

    public function paymentTypes(){
        return $this->hasMany(PaymentType::class,'payment_method_id');
    }

}

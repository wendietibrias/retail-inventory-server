<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentType extends Model
{
    protected $table = 'payment_types';
    protected $fillable = [
        'name',
        'code',
        'description',
        'total_payment'
    ];
}

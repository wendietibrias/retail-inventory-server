<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'settings';
    protected $fillable = [
        'morning_shift_time',
        'night_shift_time',
        'no_tax_invoice_code',
        'tax_invoice_code',
        'tax',
    ];
}

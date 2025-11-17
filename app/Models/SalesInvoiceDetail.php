<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesInvoiceDetail extends Model
{
    protected $table = 'sales_invoice_details';

    protected $fillable = [
        'product_name',
        'sales_invoice_id',
        'qty',
        'product_price',
        'sub_total',
        'discount',
        'product_code',
        'description',
        'product_type_id',
    ];
}

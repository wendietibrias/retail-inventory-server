<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class SalesInvoice extends Model
{
    use Notifiable;

    protected $table = 'sales_invoices';

    protected $fillable = [
        'code',
        'customer_name',
        'warehouse',
        'customer_phone',
        'sales_person_name',
        'description',
        'created_by_id',
        'updated_by_id',
        'void_by_id',
        'status',
        'type',
        'price_type',
        'sub_total',
        'grand_total',
        'tax',
        'tax_value',
        'paid_amount',
        'grand_total_left',
        'other_code',
    ];

    /** relations */
    public function salesInvoiceDetails()
    {
        return $this->hasMany(SalesInvoiceDetail::class, 'sales_invoice_id');
    }

    public function leasing()
    {
        return $this->belongsTo(Leasing::class, 'leasing_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    public function receiveables()
    {
        return $this->hasMany(Receiveable::class, 'sales_invoice_id');
    }

    public function shiftTransaction()
    {
        return $this->hasOne(ShiftTransaction::class, 'sales_invoice_id');
    }

    public function voidBy()
    {
        return $this->belongsTo(User::class, 'void_by)id');
    }


    public function salesInvoiceLogs()
    {
        return $this->hasMany(SalesInvoiceLog::class, 'sales_invoice_id');
    }
}

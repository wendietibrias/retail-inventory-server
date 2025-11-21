<?php

namespace App\Helper;

use App\Enums\SalesInvoicePriceTypeEnum;
use App\Enums\SalesInvoiceTypeEnum;
use App\Models\TransactionSummarize;
use App\Models\TransactionSummarizeDetail;
use App\Models\TransactionSummarizeDetailpayment;
use Carbon\Carbon;

class UpdateTransactionSummarize
{
    public static function updatePpn($shiftTransaction, $salesInvoice)
    {
        $now = Carbon::now();
        $findTransaction = TransactionSummarize::whereDate('created_at', $now)->first();
        $findTransactionDetail = TransactionSummarizeDetail::where('transaction_summarize_id', $findTransaction->get('id'))->first();
        $findTransactionPayment = TransactionSummarizeDetailpayment::where('tsd_id', $findTransactionDetail->get('id'))->first();


    }

    public function updateLeasingReceiveablePayment(){}

    public function updateReceiveablePayment(){}

    public static function update($shiftTransaction,$shiftType, $salesInvoice)
    {
        $now = Carbon::now();
        $findTransaction = TransactionSummarize::whereDate('created_at', $now)->first();
        $findTransactionDetail = TransactionSummarizeDetail::where('transaction_summarize_id', $findTransaction->get('id'))->where('shift_type',$shiftType)->first();

        if ($shiftTransaction->get('down_payment_amount') > 0) {
            if ($salesInvoice->get('leasing_id')) {
                $findTransactionDetail->leasing_down_payment_total += $shiftTransaction->get('down_payment_amount');
            } else {
                $findTransactionDetail->down_payment_total += $shiftTransaction->get('down_payment_amount');
            }
        }

        if ($salesInvoice->price_type === SalesInvoicePriceTypeEnum::DEALER) {
            $findTransactionDetail->dealer_total += $shiftTransaction->get('total_paid_amount');
        }
        if ($salesInvoice->price_type === SalesInvoicePriceTypeEnum::ONLINE) {
            $findTransactionDetail->online_total = $findTransactionDetail->get('online_total') + $shiftTransaction->get('total_paid_amount');
        }
        if ($salesInvoice->price_type === SalesInvoicePriceTypeEnum::RETAIL) {
            $findTransactionDetail->retail_total += $shiftTransaction->get('total_paid_amount');

            if ($salesInvoice->type === SalesInvoiceTypeEnum::PPN) {
                $findTransactionDetail->non_ppn_total += $shiftTransaction->get('retail_total');
                $findTransaction->non_ppn_total += $findTransactionDetail->non_ppn_total;
            }
        }
        if ($salesInvoice->price_type === SalesInvoicePriceTypeEnum::SHOWCASE) {
            $findTransactionDetail->showcase_total += $shiftTransaction->get('total_paid_amount');
            $findTransaction->non_ppn_total += $shiftTransaction->get('total_paid_amount');
        }

        $findTransaction->save();
        $findTransactionDetail->save();
    }
}
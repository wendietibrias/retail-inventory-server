<?php 

namespace App\Helper;

use App\Enums\SalesInvoicePriceTypeEnum;
use App\Models\TransactionSummarize;
use App\Models\TransactionSummarizeDetail;
use App\Models\TransactionSummarizeDetailpayment;
use Carbon\Carbon;

class UpdateTransactionSummarize {
    public static function update($shiftTransaction,$salesInvoice){
        $now = Carbon::now();
        $findTransaction = TransactionSummarize::whereDate('created_at',$now)->first();
        $findTransactionDetail = TransactionSummarizeDetail::where('transaction_summarize_id',$findTransaction->get('id'))->first();
        $findTransactionPayment = TransactionSummarizeDetailpayment::where('tsd_id', $findTransactionDetail->get('id'))->first();

        if($salesInvoice->price_type === SalesInvoicePriceTypeEnum::DEALER){
            $findTransactionDetail->dealer_total = $findTransactionDetail->get('dealer_total')+$shiftTransaction->get('dealer_total');
        }
        if($salesInvoice->price_type === SalesInvoicePriceTypeEnum::ONLINE){
            $findTransactionDetail->online_total = $findTransactionDetail->get('online_total') + $shiftTransaction->get('total_paid_amount');
        }
        if($salesInvoice->price_type === SalesInvoicePriceTypeEnum::RETAIL){
            $findTransactionDetail->retail_total = $findTransactionDetail->get('retail_total') + $shiftTransaction->get('retail_total');
            $findTransactionDetail->total_ppn_sales = $findTransactionDetail->retail_total;
        }
        if($salesInvoice->price_type === SalesInvoicePriceTypeEnum::SHOWCASE){
            $findTransactionDetail->showcase_total = $findTransactionDetail->get('showcase_total') + $shiftTransaction->get('showcase_total');
        }
        
        if($findTransactionPayment){

        }
    }
}
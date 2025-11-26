<?php

namespace App\Helper;

use App\Enums\SalesInvoiceDetailProductTypeEnum;
use App\Enums\SalesInvoicePriceTypeEnum;
use App\Enums\SalesInvoiceTypeEnum;
use App\Models\TransactionSummarize;
use App\Models\TransactionSummarizeDetail;
use App\Models\TransactionSummarizeDetailpayment;
use Carbon\Carbon;

use function strtolower;
use function str_contains;

class UpdateTransactionSummarize
{
    public static function updateInternalFee($payload)
    {
        $internalFee = $payload['internal_fee'];
        $shiftType = $payload['shift_type'];
        $invoiceType = $payload['invoice_type'];
        $now = Carbon::now();

        $transactionSummarize = TransactionSummarize::where('deleted_at', null)->where('created_at', $now)->first();
        $transactionSummarizeDetail = TransactionSummarizeDetail::where('deleted_at', null)->where('invoice_type', $invoiceType)->where('shift_type', $shiftType);

        $transactionSummarize->internal_fee_total += $internalFee;
        $transactionSummarizeDetail->internal_fee_total += $internalFee;

        $transactionSummarize->save();
        $transactionSummarizeDetail->save();
    }

    public static function updateSummarize($payload)
    {
        $now = Carbon::now();

        $shiftType = $payload['shift_type'];
        $invoiceType = $payload['invoice_type'];
        $downPaymentMethod = $payload['down_payment_method_id'];
        $otherPaymentMethod = $payload['other_payment_method_id'];
        $salesInvoice = $payload['sales_invoice'];

        $taxAmount = $payload['tax_amount'];

        $paymentMethod = $payload['payment_method_id'];
        $downPaymentAmount = $payload['down_payment_amount'];
        $otherPaymentAmount = $payload['other_payment_amount'];
        $paidAmount = $payload['paid_amount'];
        $leasingFee = $payload['leasing_fee'];

        $transactionSummarize = TransactionSummarize::where('deleted_at', null)->where('created_at', $now)->first();
        $transactionSummarizeDetail = TransactionSummarizeDetail::where('deleted_at', null)->where('invoice_type', $invoiceType)->where('shift_type', $shiftType);

        /** First update the payment method */

        $findPaymentMethod = TransactionSummarizeDetailpayment::where('tsd_id', $transactionSummarizeDetail->id)->where('deleted_at', null)->where('pm_detail_id', $paymentMethod)->first();
        $findOtherPaymentMethod = TransactionSummarizeDetailpayment::where('tsd_id', $transactionSummarizeDetail->id)->where('deleted_at', null)->where('pm_detail_id', $otherPaymentMethod)->first();
        $findDownPaymentMethod = TransactionSummarizeDetailpayment::where('tsd_id', $transactionSummarizeDetail->id)->where('deleted_at', null)->where('pm_detail_id', $downPaymentMethod)->first();

        if ($findPaymentMethod) {
            if ($salesInvoice['leasing_id']) {
                if (
                    (str_contains(strtolower($findPaymentMethod->paymentMethod->name), 'leasing') ||
                        str_contains(strtolower($findPaymentMethod->paymentMethod->name), 'leasing'))
                ) {
                    $transactionSummarize->leasing_receiveable_total += $salesInvoice->grand_total + $taxAmount;
                    $transactionSummarizeDetail->leasing_receiveable_total += $salesInvoice->grand_total + $taxAmount;
                    if ($downPaymentAmount > 0) {
                        $findPaymentMethod->total_paid_amount += $salesInvoice->grand_total - $downPaymentAmount;
                        $transactionSummarizeDetail->leasing_receiveable_total -= $downPaymentAmount;
                        $transactionSummarize->leasing_receiveable_total -= $downPaymentAmount;
                    } else {
                        $findPaymentMethod->total_paid_amount += $salesInvoice->grand_total;
                    }
                }
                if (
                    str_contains(strtolower($findPaymentMethod->name), 'debit')
                ) {
                    $transactionSummarize->leasing_debit_total += $paidAmount;
                    $transactionSummarizeDetail->leasing_debit_total += $paidAmount;
                }
                if (
                    str_contains(strtolower($findPaymentMethod->paymentMethod->name), 'transfer')
                ) {
                    $transactionSummarize->leasing_transfer_total += $paidAmount;
                    $transactionSummarizeDetail->leasing_transfer_total += $paidAmount;
                }
                if (
                    str_contains(strtolower($findPaymentMethod->paymentMethod->name), 'qr')
                ) {
                    $transactionSummarize->leasing_qr_total += $paidAmount;
                    $transactionSummarizeDetail->leasing_qr_total += $paidAmount;
                }
                if (
                    str_contains(strtolower($findPaymentMethod->paymentMethod->name), 'cash')
                ) {
                    $transactionSummarize->leasing_cash_total += $paidAmount;
                    $transactionSummarizeDetail->leasing_cash_total += $paidAmount;
                }
            } else {
                if (
                    (str_contains(strtolower($findPaymentMethod->paymentMethod->name), 'kredit') ||
                        str_contains(strtolower($findPaymentMethod->paymentMethod->name), 'kredit'))
                ) {
                    $transactionSummarize->receiveable_total += $salesInvoice->grand_total;
                    $transactionSummarizeDetail->receiveable_total += $salesInvoice->grand_total;

                    if ($downPaymentAmount > 0) {
                        $transactionSummarizeDetail->receiveable_total -= $downPaymentAmount;
                        $transactionSummarize->receiveable_total -= $downPaymentAmount;
                    }
                }
                if (
                    str_contains(strtolower($findPaymentMethod->name), 'debit')
                ) {

                    $transactionSummarize->debit_total += $paidAmount;
                    $transactionSummarizeDetail->debit_total += $paidAmount;
                }
                if (
                    str_contains(strtolower($findPaymentMethod->paymentMethod->name), 'transfer')
                ) {
                    $transactionSummarize->transfer_total += $paidAmount;
                    $transactionSummarizeDetail->transfer_total += $paidAmount;
                }
                if (
                    str_contains(strtolower($findPaymentMethod->paymentMethod->name), 'qr')
                ) {
                    $transactionSummarize->qr_total += $paidAmount;
                    $transactionSummarizeDetail->qr_total += $paidAmount;
                }
                if (
                    str_contains(strtolower($findPaymentMethod->paymentMethod->name), 'cash')
                ) {
                    $transactionSummarize->cash_total += $paidAmount;
                    $transactionSummarizeDetail->cash_total += $paidAmount;
                }
            }

            $findPaymentMethod->save();
        }

        if ($findOtherPaymentMethod) {
            $findPaymentMethod->total_paid_amount += $otherPaymentAmount;
            if ($salesInvoice['leasing_id']) {
                if (
                    str_contains(strtolower($findOtherPaymentMethod->name), 'debit')
                ) {
                    $transactionSummarizeDetail->leasing_debit_total += $otherPaymentAmount;
                }
                if (
                    str_contains(strtolower($findOtherPaymentMethod->paymentMethod->name), 'transfer')
                ) {
                    $transactionSummarizeDetail->leasing_transfer_total += $otherPaymentAmount;
                }
                if (
                    str_contains(strtolower($findOtherPaymentMethod->paymentMethod->name), 'qr')
                ) {
                    $transactionSummarizeDetail->leasing_qr_total += $otherPaymentAmount;
                }
                if (
                    str_contains(strtolower($findOtherPaymentMethod->paymentMethod->name), 'cash')
                ) {
                    $transactionSummarizeDetail->leasing_cash_total += $otherPaymentAmount;
                }
            } else {
                if (
                    str_contains(strtolower($findOtherPaymentMethod->name), 'debit')
                ) {

                    $transactionSummarizeDetail->debit_total += $otherPaymentAmount;
                }
                if (
                    str_contains(strtolower($findOtherPaymentMethod->paymentMethod->name), 'transfer')
                ) {
                    $transactionSummarizeDetail->transfer_total += $otherPaymentAmount;
                }
                if (
                    str_contains(strtolower($findOtherPaymentMethod->paymentMethod->name), 'qr')
                ) {
                    $transactionSummarizeDetail->qr_total += $otherPaymentAmount;
                }
                if (
                    str_contains(strtolower($findOtherPaymentMethod->paymentMethod->name), 'cash')
                ) {
                    $transactionSummarizeDetail->cash_total += $otherPaymentAmount;
                }
            }

            $findOtherPaymentMethod->save();
        }

        if ($findDownPaymentMethod) {
            $findDownPaymentMethod->total_paid_amount += $downPaymentAmount;
            if ($salesInvoice['leasing_id']) {
                $transactionSummarizeDetail->leasing_down_payment_total += $downPaymentAmount;
                $transactionSummarize->leasing_down_payment_total += $downPaymentAmount;
                if (
                    (str_contains(strtolower($findDownPaymentMethod->paymentMethod->name), 'kredit') ||
                        str_contains(strtolower($findDownPaymentMethod->paymentMethod->name), 'kredit'))
                ) {
                    $transactionSummarizeDetail->leasing_receiveable_total += (intval($salesInvoice->grand_total) - $paidAmount);
                }
                if (
                    str_contains(strtolower($findDownPaymentMethod->name), 'debit')
                ) {
                    $transactionSummarizeDetail->leasing_debit_total += $paidAmount;
                }
                if (
                    str_contains(strtolower($findDownPaymentMethod->paymentMethod->name), 'transfer')
                ) {
                    $transactionSummarizeDetail->leasing_transfer_total += $paidAmount;
                }
                if (
                    str_contains(strtolower($findDownPaymentMethod->paymentMethod->name), 'qr')
                ) {
                    $transactionSummarizeDetail->leasing_qr_total += $paidAmount;
                }
                if (
                    str_contains(strtolower($findDownPaymentMethod->paymentMethod->name), 'cash')
                ) {
                    $transactionSummarizeDetail->leasing_cash_total += $paidAmount;
                }
            } else {
                $transactionSummarizeDetail->down_payment_total += $downPaymentAmount;
                $transactionSummarize->down_payment_total += $downPaymentAmount;
                if (
                    (str_contains(strtolower($findDownPaymentMethod->paymentMethod->name), 'kredit') ||
                        str_contains(strtolower($findDownPaymentMethod->paymentMethod->name), 'kredit'))
                ) {
                    $transactionSummarizeDetail->receiveable_total += (intval($salesInvoice->grand_total) - $paidAmount);
                }
                if (
                    str_contains(strtolower($findDownPaymentMethod->name), 'debit')
                ) {
                    $transactionSummarizeDetail->debit_total += $paidAmount;
                }
                if (
                    str_contains(strtolower($findDownPaymentMethod->paymentMethod->name), 'transfer')
                ) {
                    $transactionSummarizeDetail->transfer_total += $paidAmount;
                }
                if (
                    str_contains(strtolower($findDownPaymentMethod->paymentMethod->name), 'qr')
                ) {
                    $transactionSummarizeDetail->qr_total += $paidAmount;
                }
                if (
                    str_contains(strtolower($findDownPaymentMethod->paymentMethod->name), 'cash')
                ) {
                    $transactionSummarizeDetail->cash_total += $paidAmount;
                }
            }
            $findDownPaymentMethod->save();
        }

        if ($salesInvoice['price_type'] === SalesInvoicePriceTypeEnum::DEALER) {
            $transactionSummarizeDetail->dealer_total += $salesInvoice->grand_total;
            $transactionSummarize->dealer_total += $salesInvoice->grand_total;
        }
        if ($salesInvoice['price_type'] === SalesInvoicePriceTypeEnum::ONLINE) {
            $transactionSummarizeDetail->online_total += $salesInvoice->grand_total;
            $transactionSummarize->online_total += $salesInvoice->grand_total;

        }
        if ($salesInvoice['price_type'] === SalesInvoicePriceTypeEnum::SHOWCASE) {
            $transactionSummarize->showcase_total += $salesInvoice->grand_total;
            $transactionSummarizeDetail->showcase_total += $salesInvoice->grand_total;
        }
        if ($salesInvoice['price_type'] === SalesInvoicePriceTypeEnum::RETAIL) {
            if ($salesInvoice->type === SalesInvoiceTypeEnum::PPN) {
                $transactionSummarize->ppn_total += $salesInvoice->grand_total;
                $transactionSummarizeDetail->ppn_total += $salesInvoice->grand_total;
            }
            $transactionSummarizeDetail->retail_total += $salesInvoice->grand_total;
            $transactionSummarize->retail_total += $salesInvoice->grand_total;
        }

        /** Update Sales Invoice  Details By It's Item Type */

        foreach ($salesInvoice->salesInvoiceDetails as $salesInvoiceDetail) {
            if ($salesInvoiceDetail['product_type'] === SalesInvoiceDetailProductTypeEnum::BARANG_BESAR) {
                $transactionSummarize->big_item_total += $salesInvoiceDetail->sub_total;
                $transactionSummarizeDetail->big_item_total += $salesInvoiceDetail->sub_total;
            } else if ($salesInvoiceDetail['product_type'] === SalesInvoiceDetailProductTypeEnum::BARANG_LEASING) {
                $transactionSummarize->leasing_item_total += $salesInvoiceDetail->sub_total;
                $transactionSummarizeDetail->leasing_item_total += $salesInvoiceDetail->sub_total;
            } else {
                $transactionSummarize->item_total += $salesInvoiceDetail->sub_total;
                $transactionSummarizeDetail->item_total += $salesInvoiceDetail->sub_total;
            }
        }

        if (!$salesInvoice->is_in_paid && $salesInvoice->tax_amount > 0) {
            $transactionSummarize->tax_total += $salesInvoice->tax_amount;
            $transactionSummarizeDetail->tax_total += $salesInvoice->tax_amount;
        }

        if ($leasingFee && intval($leasingFee) > 0) {
            $transactionSummarize->leasing_fee_total += $leasingFee;
            $transactionSummarizeDetail->leasing_fee_total += $leasingFee;
        }

        $transactionSummarizeDetail->save();
        $transactionSummarize->save();
    }

    public static function updateReceiveable($payload)
    {

        $now = Carbon::now();

        $shiftType = $payload['shift_type'];
        $invoiceType = $payload['invoice_type'];
        $otherPaymentMethod = $payload['other_payment_method_id'];
        $salesInvoice = $payload['sales_invoice'];


        $paymentMethod = $payload['payment_method_id'];
        $otherPaymentAmount = $payload['other_payment_amount'];
        $paidAmount = $payload['paid_amount'];

        $transactionSummarize = TransactionSummarize::where('deleted_at', null)->where('created_at', $now)->first();
        $transactionSummarizeDetail = TransactionSummarizeDetail::where('deleted_at', null)->where('invoice_type', $invoiceType)->where('shift_type', $shiftType);

        /** First update the payment method */

        $findPaymentMethod = TransactionSummarizeDetailpayment::where('tsd_id', $transactionSummarizeDetail->id)->where('deleted_at', null)->where('pm_detail_id', $paymentMethod)->first();
        $findOtherPaymentMethod = TransactionSummarizeDetailpayment::where('tsd_id', $transactionSummarizeDetail->id)->where('deleted_at', null)->where('pm_detail_id', $otherPaymentMethod)->first();

        if ($findPaymentMethod) {
            if ($salesInvoice->leasing_id) {
                $transactionSummarize->leasing_receiveable_paid_total += $paidAmount;
            } else {
                $transactionSummarize->receiveable_paid_total += $paidAmount;
            }
            $findPaymentMethod->total_paid_amount += $paidAmount;
            if (
                str_contains(strtolower($findPaymentMethod->name), 'debit')
            ) {
                $transactionSummarize->debit_total += $paidAmount;
                $transactionSummarizeDetail->debit_total += $paidAmount;
            }
            if (
                str_contains(strtolower($findPaymentMethod->paymentMethod->name), 'transfer')
            ) {
                $transactionSummarize->transfer_total += $paidAmount;
                $transactionSummarizeDetail->transfer_total += $paidAmount;
            }
            if (
                str_contains(strtolower($findPaymentMethod->paymentMethod->name), 'qr')
            ) {
                $transactionSummarize->qr_total += $paidAmount;
                $transactionSummarizeDetail->qr_total += $paidAmount;
            }
            if (
                str_contains(strtolower($findPaymentMethod->paymentMethod->name), 'cash')
            ) {
                $transactionSummarize->cash_total += $paidAmount;
                $transactionSummarizeDetail->cash_total += $paidAmount;
            }
        }
        if ($findOtherPaymentMethod) {
            if ($salesInvoice->leasing_id) {
                $transactionSummarize->leasing_receiveable_paid_total += $otherPaymentAmount;
                if (
                    str_contains(strtolower($findPaymentMethod->name), 'debit')
                ) {
                    $transactionSummarize->leasing_debit_total += $paidAmount;
                    $transactionSummarizeDetail->leasing_debit_total += $paidAmount;
                }
                if (
                    str_contains(strtolower($findPaymentMethod->paymentMethod->name), 'transfer')
                ) {
                    $transactionSummarize->leasing_transfer_total += $paidAmount;
                    $transactionSummarizeDetail->leasing_transfer_total += $paidAmount;
                }
                if (
                    str_contains(strtolower($findPaymentMethod->paymentMethod->name), 'qr')
                ) {
                    $transactionSummarize->leasing_qr_total += $paidAmount;
                    $transactionSummarizeDetail->leasing_qr_total += $paidAmount;
                }
                if (
                    str_contains(strtolower($findPaymentMethod->paymentMethod->name), 'cash')
                ) {
                    $transactionSummarize->leasing_cash_total += $paidAmount;
                    $transactionSummarizeDetail->leasing_cash_total += $paidAmount;
                }
            } else {
                $transactionSummarize->receiveable_paid_total += $otherPaymentAmount;
                   if (
                    str_contains(strtolower($findPaymentMethod->name), 'debit')
                ) {
                    $transactionSummarize->debit_total += $paidAmount;
                    $transactionSummarizeDetail->debit_total += $paidAmount;
                }
                if (
                    str_contains(strtolower($findPaymentMethod->paymentMethod->name), 'transfer')
                ) {
                    $transactionSummarize->transfer_total += $paidAmount;
                    $transactionSummarizeDetail->transfer_total += $paidAmount;
                }
                if (
                    str_contains(strtolower($findPaymentMethod->paymentMethod->name), 'qr')
                ) {
                    $transactionSummarize->qr_total += $paidAmount;
                    $transactionSummarizeDetail->qr_total += $paidAmount;
                }
                if (
                    str_contains(strtolower($findPaymentMethod->paymentMethod->name), 'cash')
                ) {
                    $transactionSummarize->cash_total += $paidAmount;
                    $transactionSummarizeDetail->cash_total += $paidAmount;
                }
            }
            $findOtherPaymentMethod->total_paid_amount += $otherPaymentAmount;
        }
    }

    public static function updatePayable($payload){
        $payableTotal = $payload['payable_total'];
        $payableDate = $payload['payable_date'];
        $invoiceType = $payload['invoice_type'];
        $shiftType = $payload['shift_type'];

        if(!$payableDate){
            $payableDate = Carbon::now();
        }

        $transactionSummarize = TransactionSummarize::where('deleted_at', null)->where('created_at', $payableDate)->first();
        $transactionSummarizeDetail= TransactionSummarizeDetail::where('deleted_at',null)->where('invoice_type',$invoiceType)->where('shift_type',$shiftType)->where('ts_id',$transactionSummarize->id)->first();

        $transactionSummarizeDetail->payable_total += $payableTotal;
        $transactionSummarize->payable_total +=  $payableTotal;
        
        $transactionSummarizeDetail->save();
        $transactionSummarize->save();
    }

}
<?php

namespace App\Helper;

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
    public static function updateNonPpn($shiftTransaction, $shiftType, $salesInvoice)
    {
        $now = Carbon::now();
        $findTransaction = TransactionSummarize::whereDate('created_at', $now)->first();
        $findTransactionDetail = TransactionSummarizeDetail::where('transaction_summarize_id', $findTransaction->get('id'))
            ->where('shift_type', $shiftType)
            ->where('invoice_type', $salesInvoice->get('type'))
            ->first();

        $paymentMethodId = $shiftTransaction->get('pm_detail_id');
        $downPaymentMethodId = $shiftTransaction->get('dpm_detail_id');
        $otherPaymentMethodId = $shiftTransaction->get('opm_detail_id');

        $findPaymentMethodSum = null;
        $findDownPaymentMethodSum = null;
        $findOtherPaymentMethodSum = null;

        $findTransaction->whole_total += $shiftTransaction->get('total_paid_amount');
        $findTransactionDetail->whole_total += $shiftTransaction->get('total_paid_amount');

        if ($paymentMethodId) {
            $findPaymentMethodSum = TransactionSummarizeDetailpayment::with(['paymentMethod'])
                ->where('deleted_at', null)
                ->where('pm_detail_id', $paymentMethodId)
                ->where('tsd_id', $findTransactionDetail->get('id'))
                ->first();
        }

        if ($downPaymentMethodId) {
            $findDownPaymentMethodSum = TransactionSummarizeDetailpayment::with(['paymentMethod'])
                ->where('deleted_at', null)
                ->where('dpm_detail_id', $paymentMethodId)
                ->where('tsd_id', $findTransactionDetail->get('id'))
                ->first();
        }

        if ($otherPaymentMethodId) {
            $findOtherPaymentMethodSum = TransactionSummarizeDetailpayment::with(['paymentMethod'])
                ->where('deleted_at', null)
                ->where('opm_detail_id', $paymentMethodId)
                ->where('tsd_id', $findTransactionDetail->get('id'))
                ->first();
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
                $findTransaction->non_ppn_total += $shiftTransaction->get('retail_total');
            }
        }
        if ($salesInvoice->price_type === SalesInvoicePriceTypeEnum::SHOWCASE) {
            $findTransactionDetail->showcase_total += $shiftTransaction->get('total_paid_amount');
            $findTransaction->non_ppn_total += $shiftTransaction->get('total_paid_amount');
        }


        if ($findPaymentMethodSum) {
            if ($salesInvoice->leasing_id) {
                if (
                    str_contains(strtolower($findPaymentMethodSum->paymentMethod->name), 'kredit') ||
                    str_contains(strtolower($findPaymentMethodSum->paymentMethod->name), 'credit')
                ) {
                    $findTransactionDetail->leasing_receiveable_total += $shiftTransaction->get('paid_amount');
                    $findTransaction->leasing_receiveable_total += $shiftTransaction->get('paid_amount');
                }

                if (str_contains(strtolower($findPaymentMethodSum->paymentMethod->name), 'qr')) {
                    $findTransactionDetail->leasing_qr_total += $shiftTransaction->get('paid_amount');
                    $findTransaction->leasing_qr_total += $shiftTransaction->get('paid_amount');
                }

                if (str_contains(strtolower($findPaymentMethodSum->paymentMethod->name), 'transfer')) {
                    $findTransactionDetail->leasing_transfer_total += $shiftTransaction->get('paid_amount');
                    $findTransaction->leasing_transfer_total += $shiftTransaction->get('paid_amount');
                }
                if (str_contains(strtolower($findPaymentMethodSum->paymentMethod->name), 'debit')) {
                    $findTransactionDetail->leasing_debit_total += $shiftTransaction->get('paid_amount');
                    $findTransaction->leasing_debit_total += $shiftTransaction->get('paid_amount');
                }

                if (str_contains(strtolower($findPaymentMethodSum->paymentMethod->name), 'cash')) {
                    $findTransactionDetail->leasing_cash_total += $shiftTransaction->get('paid_amount');
                    $findTransaction->leasing_cash_total += $shiftTransaction->get('paid_amount');
                }

            } else {
                if (
                    str_contains(strtolower($findPaymentMethodSum->paymentMethod->name), 'kredit') ||
                    str_contains(strtolower($findPaymentMethodSum->paymentMethod->name), 'credit')
                ) {
                    $findTransactionDetail->receiveable_total += $shiftTransaction->get('paid_amount');
                    $findTransaction->receiveable_total += $shiftTransaction->get('paid_amount');
                }
                if (
                    str_contains(strtolower($findPaymentMethodSum->paymentMethod->name), 'debit')
                ) {
                    $findTransactionDetail->debit_total += $shiftTransaction->get('paid_amount');
                    $findTransaction->debit_total += $shiftTransaction->get('paid_amount');
                }
                if (str_contains(strtolower($findPaymentMethodSum->paymentMethod->name), 'transfer')) {
                    $findTransactionDetail->transfer_total += $shiftTransaction->get('paid_amount');
                    $findTransaction->transfer_total += $shiftTransaction->get('paid_amount');
                }
                if (str_contains(strtolower($findPaymentMethodSum->paymentMethod->name), 'debit')) {
                    $findTransactionDetail->debit_total += $shiftTransaction->get('paid_amount');
                    $findTransaction->debit_total += $shiftTransaction->get('paid_amount');
                }
                if (str_contains(strtolower($findPaymentMethodSum->paymentMethod->name), 'transfer')) {
                    $findTransactionDetail->transfer_total += $shiftTransaction->get('paid_amount');
                    $findTransaction->transfer_total += $shiftTransaction->get('paid_amount');
                }
                if (str_contains(strtolower($findDownPaymentMethodSum->name), 'cash')) {
                    $findTransactionDetail->cash_total += $shiftTransaction->get('paid_amount');
                    $findTransaction->cash_total += $shiftTransaction->get('paid_amount');
                }
            }
            $findPaymentMethodSum->total_payment += $shiftTransaction->get('paid_amount');
            $findPaymentMethodSum->save();
        }

        if ($findDownPaymentMethodSum) {
            if ($salesInvoice->leasing_id) {
                if (str_contains(strtolower($findPaymentMethodSum->paymentMethod->name), 'qr')) {
                    $findTransactionDetail->leasing_qr_total += $shiftTransaction->get('down_payment_amount');
                    $findTransaction->leasing_qr_total += $shiftTransaction->get('down_payment_amount');
                }
                if (str_contains(strtolower($findPaymentMethodSum->paymentMethod->name), 'debit')) {
                    $findTransactionDetail->leasing_debit_total += $shiftTransaction->get('down_payment_amount');
                    $findTransaction->leasing_debit_total += $shiftTransaction->get('down_payment_amount');
                }
                if (str_contains(strtolower($findPaymentMethodSum->paymentMethod->name), 'transfer')) {
                    $findTransactionDetail->leasing_transfer_total += $shiftTransaction->get('down_payment_amount');
                    $findTransaction->leasing_transfer_total += $shiftTransaction->get('down_payment_total');
                }
                if (str_contains(strtolower($findDownPaymentMethodSum->name), 'cash')) {
                    $findTransactionDetail->leasing_cash_total += $shiftTransaction->get('down_payment_amount');
                    $findTransaction->leasing_cash_total += $shiftTransaction->get('down_payment_amount');
                }
            } else {
                if (
                    str_contains(strtolower($findDownPaymentMethodSum->paymentMethod->name), 'kredit') ||
                    str_contains(strtolower($findDownPaymentMethodSum->paymentMethod->name), 'credit')
                ) {
                    $findTransactionDetail->receiveable_total += $shiftTransaction->get('down_payment_amount');
                }
                if (str_contains(strtolower($findPaymentMethodSum->paymentMethod->name), 'qr')) {
                    $findTransactionDetail->qr_total += $shiftTransaction->get('down_payment_amount');
                }
                if (str_contains(strtolower($findPaymentMethodSum->paymentMethod->name), 'debit')) {
                    $findTransactionDetail->debit_total += $shiftTransaction->get('down_payment_amount');
                }
                if (str_contains(strtolower($findPaymentMethodSum->paymentMethod->name), 'transfer')) {
                    $findTransactionDetail->transfer_total += $shiftTransaction->get('down_payment_amount');
                }
                if (str_contains(strtolower($findPaymentMethodSum->paymentMethod->name), 'cash')) {
                    $findTransactionDetail->cash_total += $shiftTransaction->get('down_payment_amount');
                }
            }

            $findDownPaymentMethodSum->total_payment += $shiftTransaction->get('down_payment_amount');
            $findDownPaymentMethodSum->save();
        }
        if ($findOtherPaymentMethodSum) {
            if ($salesInvoice->leasing_id) {

            } else {

            }
            if (
                str_contains(strtolower($findOtherPaymentMethodSum->paymentMethod->name), 'kredit') ||
                str_contains(strtolower($findOtherPaymentMethodSum->paymentMethod->name), 'credit')
            ) {
                $findTransactionDetail->receiveable_total += $shiftTransaction->get('other_payment_amount');
            }
            $findOtherPaymentMethodSum->total_payment += $shiftTransaction->get('other_payment_amount');
            $findOtherPaymentMethodSum->save();
        }

        $findTransaction->save();
        $findTransactionDetail->save();
    }

    public function updateLeasingReceiveablePayment()
    {

    }

    public function updateReceiveablePayment()
    {

    }

    public static function updatePpn($shiftTransaction, $shiftType, $salesInvoice)
    {
        $now = Carbon::now();
        $findTransaction = TransactionSummarize::whereDate('created_at', $now)->first();
        $findTransactionDetail = TransactionSummarizeDetail::where('transaction_summarize_id', $findTransaction->get('id'))
            ->where('shift_type', $shiftType)
            ->where('invoice_type', $salesInvoice->get('type'))
            ->first();

        $paymentMethodId = $shiftTransaction->get('pm_detail_id');
        $downPaymentMethodId = $shiftTransaction->get('dpm_detail_id');
        $otherPaymentMethodId = $shiftTransaction->get('opm_detail_id');

        $findPaymentMethodSum = null;
        $findDownPaymentMethodSum = null;
        $findOtherPaymentMethodSum = null;

        $findTransaction->whole_total += $shiftTransaction->get('total_paid_amount');
        $findTransactionDetail->whole_total += $shiftTransaction->get('total_paid_amount');

        $findTransactionDetail->tax_total += $shiftTransaction->get('tax_amount');
        $findTransactionDetail->tax_total += $shiftTransaction->get('tax_amount');

        if ($paymentMethodId) {
            $findPaymentMethodSum = TransactionSummarizeDetailpayment::with(['paymentMethod'])
                ->where('deleted_at', null)
                ->where('pm_detail_id', $paymentMethodId)
                ->where('tsd_id', $findTransactionDetail->get('id'))
                ->first();
        }

        if ($downPaymentMethodId) {
            $findDownPaymentMethodSum = TransactionSummarizeDetailpayment::with(['paymentMethod'])
                ->where('deleted_at', null)
                ->where('dpm_detail_id', $paymentMethodId)
                ->where('tsd_id', $findTransactionDetail->get('id'))
                ->first();
        }

        if ($otherPaymentMethodId) {
            $findOtherPaymentMethodSum = TransactionSummarizeDetailpayment::with(['paymentMethod'])
                ->where('deleted_at', null)
                ->where('opm_detail_id', $paymentMethodId)
                ->where('tsd_id', $findTransactionDetail->get('id'))
                ->first();
        }

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
            $findTransactionDetail->ppn_total += $shiftTransaction->get('retail_total');
            $findTransaction->ppn_total += $shiftTransaction->get('retail_total');
        }
        if ($salesInvoice->price_type === SalesInvoicePriceTypeEnum::SHOWCASE) {
            $findTransactionDetail->showcase_total += $shiftTransaction->get('total_paid_amount');
            $findTransaction->ppn_total += $shiftTransaction->get('total_paid_amount');
        }


        if ($findPaymentMethodSum) {
            if ($salesInvoice->leasing_id) {
                if (
                    str_contains(strtolower($findPaymentMethodSum->paymentMethod->name), 'leasing') ||
                    str_contains(strtolower($findPaymentMethodSum->paymentMethod->name), 'leasing')
                ) {
                    $findTransactionDetail->leasing_receiveable_total += $shiftTransaction->get('paid_amount');
                    $findTransaction->leasing_receiveable_total += $shiftTransaction->get('paid_amount');
                }

                if (str_contains(strtolower($findPaymentMethodSum->paymentMethod->name), 'qr')) {
                    $findTransactionDetail->leasing_qr_total += $shiftTransaction->get('paid_amount');
                    $findTransaction->leasing_qr_total += $shiftTransaction->get('paid_amount');
                }

                if (str_contains(strtolower($findPaymentMethodSum->paymentMethod->name), 'transfer')) {
                    $findTransactionDetail->leasing_transfer_total += $shiftTransaction->get('paid_amount');
                    $findTransaction->leasing_transfer_total += $shiftTransaction->get('paid_amount');
                }
                if (str_contains(strtolower($findPaymentMethodSum->paymentMethod->name), 'debit')) {
                    $findTransactionDetail->leasing_debit_total += $shiftTransaction->get('paid_amount');
                    $findTransaction->leasing_debit_total += $shiftTransaction->get('paid_amount');
                }

                if (str_contains(strtolower($findPaymentMethodSum->paymentMethod->name), 'cash')) {
                    $findTransactionDetail->leasing_cash_total += $shiftTransaction->get('paid_amount');
                    $findTransaction->leasing_cash_total += $shiftTransaction->get('paid_amount');
                }

            } else {
                if (
                    str_contains(strtolower($findPaymentMethodSum->paymentMethod->name), 'kredit') ||
                    str_contains(strtolower($findPaymentMethodSum->paymentMethod->name), 'credit')
                ) {
                    $findTransactionDetail->receiveable_total += $shiftTransaction->get('paid_amount');
                    $findTransaction->receiveable_total += $shiftTransaction->get('paid_amount');
                }
                if (
                    str_contains(strtolower($findPaymentMethodSum->paymentMethod->name), 'debit')
                ) {
                    $findTransactionDetail->debit_total += $shiftTransaction->get('paid_amount');
                    $findTransaction->debit_total += $shiftTransaction->get('paid_amount');
                }
                if (str_contains(strtolower($findPaymentMethodSum->paymentMethod->name), 'transfer')) {
                    $findTransactionDetail->transfer_total += $shiftTransaction->get('paid_amount');
                    $findTransaction->transfer_total += $shiftTransaction->get('paid_amount');
                }
                if (str_contains(strtolower($findPaymentMethodSum->paymentMethod->name), 'debit')) {
                    $findTransactionDetail->debit_total += $shiftTransaction->get('paid_amount');
                    $findTransaction->debit_total += $shiftTransaction->get('paid_amount');
                }
                if (str_contains(strtolower($findPaymentMethodSum->paymentMethod->name), 'transfer')) {
                    $findTransactionDetail->transfer_total += $shiftTransaction->get('paid_amount');
                    $findTransaction->transfer_total += $shiftTransaction->get('paid_amount');
                }
                if (str_contains(strtolower($findDownPaymentMethodSum->name), 'cash')) {
                    $findTransactionDetail->cash_total += $shiftTransaction->get('paid_amount');
                    $findTransaction->cash_total += $shiftTransaction->get('paid_amount');
                }
            }
            $findPaymentMethodSum->total_payment += $shiftTransaction->get('paid_amount');
            $findPaymentMethodSum->save();
        }

        if ($findDownPaymentMethodSum) {
            if ($salesInvoice->leasing_id) {
                if (str_contains(strtolower($findDownPaymentMethodSum->paymentMethod->name), 'qr')) {
                    $findTransactionDetail->leasing_qr_total += $shiftTransaction->get('down_payment_amount');
                    $findTransaction->leasing_qr_total += $shiftTransaction->get('down_payment_amount');
                }
                if (str_contains(strtolower($findDownPaymentMethodSum->paymentMethod->name), 'debit')) {
                    $findTransactionDetail->leasing_debit_total += $shiftTransaction->get('down_payment_amount');
                    $findTransaction->leasing_debit_total += $shiftTransaction->get('down_payment_amount');
                }
                if (str_contains(strtolower($findDownPaymentMethodSum->paymentMethod->name), 'transfer')) {
                    $findTransactionDetail->leasing_transfer_total += $shiftTransaction->get('down_payment_amount');
                    $findTransaction->leasing_transfer_total += $shiftTransaction->get('down_payment_total');
                }
                if (str_contains(strtolower($findDownPaymentMethodSum->name), 'cash')) {
                    $findTransactionDetail->leasing_cash_total += $shiftTransaction->get('down_payment_amount');
                    $findTransaction->leasing_cash_total += $shiftTransaction->get('down_payment_amount');
                }
            } else {
                if (str_contains(strtolower($findDownPaymentMethodSum->paymentMethod->name), 'qr')) {
                    $findTransactionDetail->qr_total += $shiftTransaction->get('down_payment_amount');
                }
                if (str_contains(strtolower($findDownPaymentMethodSum->paymentMethod->name), 'debit')) {
                    $findTransactionDetail->debit_total += $shiftTransaction->get('down_payment_amount');
                }
                if (str_contains(strtolower($findDownPaymentMethodSum->paymentMethod->name), 'transfer')) {
                    $findTransactionDetail->transfer_total += $shiftTransaction->get('down_payment_amount');
                }
                if (str_contains(strtolower($findDownPaymentMethodSum->paymentMethod->name), 'cash')) {
                    $findTransactionDetail->cash_total += $shiftTransaction->get('down_payment_amount');
                }
            }

            $findDownPaymentMethodSum->total_payment += $shiftTransaction->get('down_payment_amount');
            $findDownPaymentMethodSum->save();
        }
        if ($findOtherPaymentMethodSum) {
            if ($salesInvoice->leasing_id) {
                if (str_contains(strtolower($findOtherPaymentMethodSum->paymentMethod->name), 'qr')) {
                    $findTransactionDetail->leasing_qr_total += $shiftTransaction->get('other_payment_amount');
                }
                if (str_contains(strtolower($findOtherPaymentMethodSum->paymentMethod->name), 'debit')) {
                    $findTransactionDetail->leasing_debit_total += $shiftTransaction->get('other_payment_amount');
                }
                if (str_contains(strtolower($findOtherPaymentMethodSum->paymentMethod->name), 'transfer')) {
                    $findTransactionDetail->leasing_transfer_total += $shiftTransaction->get('other_payment_amount');
                }
                if (str_contains(strtolower($findOtherPaymentMethodSum->paymentMethod->name), 'cash')) {
                    $findTransactionDetail->leasing_cash_total += $shiftTransaction->get('other_payment_amount');
                }
            } else {
                if (
                    str_contains(strtolower($findOtherPaymentMethodSum->paymentMethod->name), 'kredit') ||
                    str_contains(strtolower($findOtherPaymentMethodSum->paymentMethod->name), 'credit')
                ) {
                    $findTransactionDetail->receiveable_total += $shiftTransaction->get('other_payment_amount');
                }
                if (str_contains(strtolower($findOtherPaymentMethodSum->paymentMethod->name), 'qr')) {
                    $findTransactionDetail->qr_total += $shiftTransaction->get('down_payment_amount');
                }
                if (str_contains(strtolower($findOtherPaymentMethodSum->paymentMethod->name), 'debit')) {
                    $findTransactionDetail->debit_total += $shiftTransaction->get('down_payment_amount');
                }
                if (str_contains(strtolower($findOtherPaymentMethodSum->paymentMethod->name), 'transfer')) {
                    $findTransactionDetail->transfer_total += $shiftTransaction->get('down_payment_amount');
                }
                if (str_contains(strtolower($findOtherPaymentMethodSum->paymentMethod->name), 'cash')) {
                    $findTransactionDetail->cash_total += $shiftTransaction->get('down_payment_amount');
                }
            }

            $findOtherPaymentMethodSum->total_payment += $shiftTransaction->get('other_payment_amount');
            $findOtherPaymentMethodSum->save();
        }

        $findTransaction->save();
        $findTransactionDetail->save();
    }

    private function handlePaymentMethodCalculation()
    {
    }

    private function handleDownPaymentMethodCalculation()
    {
    }

    private function handleOtherPaymentMethodCalculation()
    {
    }
}
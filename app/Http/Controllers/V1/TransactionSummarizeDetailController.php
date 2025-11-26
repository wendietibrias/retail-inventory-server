<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\SalesInvoiceDetail;
use App\Models\TransactionSummarizeDetail;
use App\Models\TransactionSummarizeDetailpayment;
use Carbon\Carbon;
use DB;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Psr\Http\Client\NetworkExceptionInterface;

use function count;

class TransactionSummarizeDetailController extends Controller
{
    public function indexByTransactionSummarizeDetailId($id, Request $request)
    {
        try {
            $now = Carbon::now();

            $transactionDetail = TransactionSummarizeDetail::where('deleted_at', null)->where('id', $id)->first();
            if (!$transactionDetail) {
                return $this->errorResponse("Detail  Rekapan Transaksi Tidak Ditemukan", 404, []);
            }

            $findTransactionSummarizePayment = TransactionSummarizeDetailpayment::with([
                'downPaymentMethodDetail' => function ($query) {
                    return $query->with(['paymentMethod']);
                },
                'otherPaymentMethodDetail' => function ($query) {
                    return $query->with(['paymentMethod']);
                },
                'paymentMethodDetail' => function ($query) {
                    return $query->with(['paymentMethod']);
                }
            ])->where('deleted_at', null)->where('tsd_id', $transactionDetail->id)->get();

            $data = [];
            $transactionDetailPaymentGroupByPaymentMethod = [];

            $data['transactionSummarizeDetail'] = $transactionDetail;

            foreach ($findTransactionSummarizePayment as $detailSummarizePayment) {
                if (count($transactionDetailPaymentGroupByPaymentMethod) < 1) {
                    $transactionDetailPaymentGroupByPaymentMethod[] = [
                        'payment_method_name' => $detailSummarizePayment->paymentMethod->name,
                        'total_payment' => $detailSummarizePayment->total_payment,
                        'method_fee' => $detailSummarizePayment->admin_fee,
                        'tax_amount' => $detailSummarizePayment->tax_amount,
                        'payment_method_details' => [
                            $detailSummarizePayment
                        ]
                    ];
                } else {
                    $columns = array_column($transactionDetailPaymentGroupByPaymentMethod, 'payment_method_name');
                    $column = array_search($detailSummarizePayment->paymentMethod->name, $columns, );
                    if ($column >= 0) {
                        $transactionDetailPaymentGroupByPaymentMethod[$column]['total_payment'] += $detailSummarizePayment->total_payment;
                        $transactionDetailPaymentGroupByPaymentMethod[$column]['admin_fee'] += $detailSummarizePayment->admin_fee;
                        $transactionDetailPaymentGroupByPaymentMethod[$column]['tax_amount'] += $detailSummarizePayment->tax_amount;
                        $transactionDetailPaymentGroupByPaymentMethod[$column]['payment_method_details'][] = $detailSummarizePayment;
                    } else {
                        $transactionDetailPaymentGroupByPaymentMethod[] = [
                            'payment_method_name' => $detailSummarizePayment->paymentMethod->name,
                            'total_payment' => $detailSummarizePayment->total_payment,
                            'method_fee' => $detailSummarizePayment->admin_fee,
                            'tax_amount' => $detailSummarizePayment->tax_amount,
                            'payment_method_details' => [
                                $detailSummarizePayment
                            ]
                        ];
                    }
                }
            }

              $findSalesInvoiceDetailAndGroup = SalesInvoiceDetail::query()
                ->select(
                    'sales_invoice_detail.product_code',
                    'sales_invoice_detail.product_type',
                    'leasing.name as leasing_name',
                    'leasing.code as leasing_code',
                    DB::raw('SUM(sales_invoice_detail.sub_total) as total'),
                    DB::raw('SUM(sales_invoice_detail.qty) as totalQty')
                )
                // 1. Join ke Parent (Invoice)
                // Gunakan nama tabel lengkap 'sales_invoice_detail.sales_invoice_id' agar tidak ambigu
                ->join(
                    'sales_invoice',
                    'sales_invoice_detail.sales_invoice_id',
                    '=',
                    'sales_invoice.id'
                )
                // 2. Join Nested (Invoice -> Leasing)
                // Asumsi: leasing_id ada di tabel 'sales_invoice'
                ->join(
                    'leasing',
                    'sales_invoice.leasing_id', // Pastikan mengambil dari tabel invoice
                    '=',
                    'leasing.id'
                )
                ->whereNull('leasing.deleted_at')
                ->whereNull('sales_invoice.deleted_at')
                ->whereDate('sales_invoice_detail.created_at', $now)
                ->where('sales_invoice.is_in_paid', true)
                ->where('sales_invoice.type', $transactionDetail->invoice_type)

                // GROUP BY WAJIB MENCAKUP SEMUA KOLOM NON-AGREGAT DI SELECT
                ->groupBy(
                    'sales_invoice_detail.product_type',
                    'sales_invoice_detail.product_code',
                    'leasing.name', // <--- Wajib ditambah
                    'leasing.code'  // <--- Wajib ditambah
                )
                ->get();

            $data['invoice_item_details'] = $findSalesInvoiceDetailAndGroup;

            return $this->successResponse("Berhasil Mendapatkan Rekapan Transaksi", 200, [
                'data' => $data
            ]);

        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500, []);
        } catch (QueryException $eq) {
            return $this->errorResponse($eq->getMessage(), 500, []);
        } catch (NetworkExceptionInterface $nei) {
            return $this->errorResponse($nei->getMessage(), 500, []);
        }
    }
}

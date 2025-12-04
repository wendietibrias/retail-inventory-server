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

    public function detail(Request $request, $id){
        $request->validate([
            'invoice_type'=>'nullable|string',
            'shift_type'=>'nullable|string'
        ]);

        try {

          $transactionDetail = TransactionSummarizeDetail::where('deleted_at',null)->where('id',$id);

          if(!$transactionDetail){
            return $this->errorResponse("Detail Rekapan Transaksi Tidak Ditemukan", 404, []);
          }

          return $this->successResponse("Berhasil Mendapatkan Detail Rekapan Transaksi", 200, [
            'data'=>$transactionDetail
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

<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\TransactionSummarize;
use DB;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Psr\Http\Client\NetworkExceptionInterface;

class TransactionSummarizeController extends Controller
{
  public function index(Request $request)
  {
    $request->validate([
      'page' => 'required|integer',
      'per_page' => 'required|integer',
    ]);

    try {
      $perPage = $request->get('per_page');
      $summarize = TransactionSummarize::with(['cashierShift'])->where('deleted_at', null);

      if ($request->has('start_date') && $request->has('end_date')) {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $summarize->whereBetween('created_at', [$startDate, $endDate]);
      }

      return $this->successResponse("Berhasil Mendapatkan Rekapan Transaksi", 200, [
        'items' => $summarize->paginate($perPage)
      ]);

    } catch (Exception $e) {
      return $this->errorResponse($e->getMessage(), 500, []);
    } catch (QueryException $eq) {
      return $this->errorResponse($eq->getMessage(), 500, []);
    } catch (NetworkExceptionInterface $nei) {
      return $this->errorResponse($nei->getMessage(), 500, []);
    }
  }

  public function detail($id)
  {
    try {
      $findDetailSummarize = TransactionSummarize::with([
        'transactionSummarizeDetails' => function ($query) {
          return $query->with([
            'transactionSummarizeDetailPayment' => function ($query) {
              return $query->with(['otherPaymentMethodDetail', 'paymentMethodDetail', 'downPaymentMethodDetail']);
            }
          ]);
        }
      ])->where('deleted_at', null)->where('id', $id)->first();

      if (!$findDetailSummarize) {
        return $this->errorResponse("Rekapan Transaksi Tidak Ditemukan", 404, []);
      }

      return $this->successResponse("Berhasil Mendapatkan Data Detail Rekapan Transaksi", 200, [
        'data' => $findDetailSummarize
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

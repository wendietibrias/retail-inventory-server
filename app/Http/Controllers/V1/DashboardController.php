<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\TransactionSummarize;
use Carbon\Carbon;
use DB;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Psr\Http\Client\NetworkExceptionInterface;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable',
            'end_date' => 'nullable',
            'type' => 'required|string',
            'month' => 'nullable|integer',
            'year' => 'nullable|integer'
        ]);

        try {
            /**
             *  Total Sales
             */

            $now = Carbon::now();

            $transactionSummarize = DB::table('transaction_summarize')
                ->select(['whole_total', 'retail_total', 'online_total', 'dealer_total', 'showcase_total', 'ppn_total', 'non_ppn_total'], )
                ->where('deleted_at', null);

            if ($request->has('type')) {
                $type = $request->get('type');
                if ($type === 'range') {
                    if ($request->has('start_date') && $request->has('end_date')) {
                        $transactionSummarize->whereBetween('created_at', [$request->get('start_date'), $request->get('end_date')]);
                    }

                }
                if ($type === 'month') {
                    $transactionSummarize->whereMonth('created_at', $request->get('month'));
                }

                if ($type === 'year') {
                    $transactionSummarize->whereYear('created_at', $request->get('year'));
                }
            }



            $data = [];

            $data['transaction_summarize_details'] = $transactionSummarize->get();



        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode(), []);

        } catch (QueryException $qeq) {
            if ($qeq->getCode() === '23000' || str_contains($qeq->getMessage(), 'Integrity constraint violation')) {
                return $this->errorResponse('error', 'Gagal menghapus! Data ini masih memiliki relasi aktif di tabel lain. Harap hapus relasi terkait terlebih dahulu.');
            }
            return $this->errorResponse("Internal Server Error", 500, []);
        } catch (NetworkExceptionInterface $nei) {
            return $this->errorResponse($nei->getMessage(), 500, []);
        }
    }
}

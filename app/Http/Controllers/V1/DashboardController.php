<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\Receiveable;
use App\Models\SalesInvoice;
use App\Models\ShiftTransaction;
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
            $now = Carbon::now();

            /**
             *  Total Sales
             */

            $latestReceiveable = Receiveable::whereDate('created_at', $now)->take(6)->get();
            ;
            $latestSalesInvoice = SalesInvoice::whereDate('created_at', $now)->take(6)->get();
            $latestShiftTransaction = ShiftTransaction::whereDate('created_at', $now)->take(6)->get();
            $transactionSummarize = DB::table('transaction_summarize')
                ->select('ppn_total', DB::raw('SUM(ppn_total) as totalPpn'))
                ->select('non_ppn_total', DB::raw('SUM(non_ppn_total) as totalNonPpn'))
                ->select('dealer_total', DB::raw('SUM(dealer_total) as totalDealer'))
                ->select('showcase_total', DB::raw('SUM(showcase_total) as totalShowcase'))
                ->select('online_total', DB::raw('SUM(online_total) as totalOnline'))
                ->select('retail_total', DB::raw('SUM(retail_total) as totalRetail'))
                ->select('whole_total', DB::raw('SUM(whole_total) as totalWhole'))
                ->select('item_total', DB::raw('SUM(item_total) as totalItem'))
                ->select('big_item_total', DB::raw('SUM(big_item_total) as totalBigItem'))
                ->select('leasing_item_total', DB::raw('SUM(leasing_item_total) as totalLeasingItem'))
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
                    $transactionSummarize->groupBy('');
                }

                if ($type === 'year') {
                    $transactionSummarize->whereYear('created_at', $request->get('year'));
                }
            }


            $data = [];

            $data['latest_receiveables'] = $latestReceiveable;
            $data['latest_shift_transactions'] = $latestShiftTransaction;
            $data['latest_sales_invoices'] = $latestSalesInvoice;
            $data['transaction_summarize'] = $transactionSummarize->get();

            return $this->successResponse("Berhasil Mendapatkan Data Dashboard", [
                'data' => $data
            ]);

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

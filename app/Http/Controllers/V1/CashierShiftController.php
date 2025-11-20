<?php

namespace App\Http\Controllers\V1;

use App\Enums\SalesInvoiceTypeEnum;
use App\Enums\ShiftStatusEnum;
use App\Enums\ShiftTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\CashierShift;
use App\Models\CashierShiftDetail;
use App\Models\TransactionSummarize;
use App\Models\TransactionSummarizeDetail;
use Carbon\Carbon;
use DB;
use Exception;
use Illuminate\Http\Request;

use function count;

class CashierShiftController extends Controller
{

    public function index(Request $request)
    {
        $request->validate([
            'page'=>'required|integer',
            'perPage'=>'required|integer',
        ]);

        try {
          $page =$request->get('page');
          $perPage = $request->get('perPage');
          $search = $request->get('search');
          $findAllShift = CashierShift::where('deleted_at',null);
          
          if($request->has('search')){
            $findAllShift->where('code', 'like',"%$search%");
          }

          return $this->successResponse("Berhasil Mendapatkan Data Shift", 200, [
            'items'=>$findAllShift->paginate($perPage)
          ]);

        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500, []);
        }
    }

    public function detail($id)
    {
        try {
            $findShift = CashierShift::with([
                'cashierShiftDetails' => function ($query) {
                    return $query->with(['shiftTransactions']);
                }
            ])->where('deleted_at', null)->first();

            if (!$findShift) {
                return $this->errorResponse("Shift Tidak Ditemukan", 404, []);
            }

            return $this->successResponse("Berhasil Mendapatkan Data Shift", 200, [
                'data' => $findShift
            ]);

        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500, []);
        }
    }

    public function openShift(Request $request)
    {
        try {
            DB::beginTransaction();

            $now = Carbon::now();
            $year = $now->year;
            $month = $now->month;
            $day = $now->day;

            $findAllOpenShift = CashierShiftDetail::where('deleted_at', null);
            $findAllOpenShift->where('status', ShiftStatusEnum::SEDANG_BERLANGSUNG);
            if (count($findAllOpenShift->get()) > 0) {
                return $this->errorResponse("Terdapat Shift Yang Belum Di Tutup Silahkan Ditutup Terlebih Dahulu", 400, []);
            }

            $countShift = CashierShift::where('deleted_at', null)->count("*") + 1;
            $createShift = CashierShift::create([
                'code' => "CS/$year/$month/$day/$countShift"
            ]);

            $saveShift = $createShift->save();
            $createShiftDetails = [];

            $transactionSummarize = null;
            $transactionSummarizeDetails = null;


            if ($saveShift) {
                $createShiftDetails = CashierShiftDetail::insert([
                    [
                        'shift_open_time' => $now,
                        'type' => ShiftTypeEnum::PAGI,
                        'status' => ShiftStatusEnum::BELUM_MULAI,
                        'cashier_shift_id' => $createShift->fresh()->get('id'),
                    ],
                    [
                        'shift_open_time' => $now,
                        'type' => ShiftTypeEnum::PAGI,
                        'status' => ShiftStatusEnum::BELUM_MULAI,
                        'cashier_shift_id' => $createShift->fresh()->get('id'),
                    ]
                ]);

                /** Create Transaction Summarize For Pagi PPN and Non PPN and Malam PPN and Non PPN */

                $transactionSummarize = TransactionSummarize::create([
                    'cashier_shift_id'=>$createShift->fresh()->get('id'),
                ]);

                $transactionSummarize->save();

                $transactionSummarize = $transactionSummarize->fresh();

                $transactionSummarizeDetails = TransactionSummarizeDetail::insert([
                    [
                        'invoice_type'=> SalesInvoiceTypeEnum::PPN,
                        'shift_type'=> ShiftTypeEnum::PAGI,
                        'cashier_shift_id'=> $transactionSummarize->get('id')
                    ],
                    [
                        'invoice_type'=> SalesInvoiceTypeEnum::PPN,
                        'shift_type'=> ShiftTypeEnum::PAGI,
                        'cashier_shift_id'=>$transactionSummarize->get('id')
                    ],
                    [
                        'invoice_type'=> SalesInvoiceTypeEnum::NON_PPN,
                        'shift_type'=> ShiftTypeEnum::MALAM,
                        'cashier_shift_id'=>$transactionSummarize->get('id')
                    ],
                    [
                        'invoice_type'=> SalesInvoiceTypeEnum::NON_PPN,
                        'shift_type'=> ShiftTypeEnum::PAGI,
                        'cashier_shift_id'=>$transactionSummarize->get('id')
                    ]
                ]);
                $transactionSummarize->save();
                $transactionSummarize->transactionSummarizeDetails()->save($transactionSummarizeDetails);
            }

            if ($createShiftDetails) {
                return $this->successResponse("Berhasil Membuka Shift", 200, [
                    'data' => $createShiftDetails
                ]);
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage(), 500, []);
        }
    }

}

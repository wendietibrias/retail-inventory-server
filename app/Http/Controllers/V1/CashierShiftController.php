<?php

namespace App\Http\Controllers\V1;

use App\Enums\PermissionEnum;
use App\Enums\SalesInvoiceTypeEnum;
use App\Enums\ShiftStatusEnum;
use App\Enums\ShiftTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\CashierShift;
use App\Models\CashierShiftDetail;
use App\Models\PaymentType;
use App\Models\TransactionSummarize;
use App\Models\TransactionSummarizeDetail;
use App\Models\TransactionSummarizeDetailpayment;
use Carbon\Carbon;
use DB;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

use Psr\Http\Client\NetworkExceptionInterface;
use function count;

class CashierShiftController extends Controller
{

    public function index(Request $request)
    {
        $request->validate([
            'page' => 'required|integer',
            'per_page' => 'required|integer',
        ]);

        try {
            $user = auth()->user();
            $page = $request->get('page');
            $perPage = $request->get('per_page');
            $search = $request->get('search');
            $findAllShift = CashierShift::with(['cashierShiftDetails', 'createdBy'])->where('deleted_at', null);

            if (!$user->hasPermissionTo(PermissionEnum::MELIHAT_SHIFT)) {
                return $this->errorResponse("Tidak Memiliki Hak Untuk Melihat Fitur Ini", 403, []);
            }

            if ($request->has('search')) {
                $findAllShift->where('code', 'like', "%$search%");
            }

            return $this->successResponse("Berhasil Mendapatkan Data Shift", 200, [
                'items' => $findAllShift->paginate($perPage)
            ]);

        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500, []);
        }
    }

    public function create(Request $request)
    {
        try {
            DB::beginTransaction();

            $user = auth()->user();
            $now = Carbon::now();

            $month = $now->month;
            $year = $now->year;

            $findAllPaymentDetails = PaymentType::all();
            $findCurrentShiftIfExist = CashierShift::where('deleted_at',null)->whereDate('created_at', $now)->first();
            $findLatestShift = CashierShift::where('deleted_at', null)->orderBy('id', 'desc')->first();

            if ($findCurrentShiftIfExist) {
                return $this->errorResponse("Terdapat Shift Yang Sudah Dibuat Pada Hari Ini", 400, []);
            }

            $latestShift = $findLatestShift ? $findLatestShift->id + 1 : 1;
            $formattedLatestShiftLastCount = null;

            if ($latestShift < 10) {
                $formattedLatestShiftLastCount = "000$latestShift";
            }
            if ($latestShift >= 10 && $latestShift < 100) {
                $formattedLatestShiftLastCount = "00$latestShift";
            }
            if ($latestShift >= 100 && $latestShift < 1000) {
                $formattedLatestShiftLastCount = "0$latestShift";
            }
            if ($latestShift >= 1000 && $latestShift <= 10000) {
                $formattedLatestShiftLastCount = "$latestShift";
            }

            /**
             * Create Shift and the details
             */
            $createShift = CashierShift::create([
                'code' => "SHIFT/$year/$month/$formattedLatestShiftLastCount",
                'created_by_id' => $user->id
            ]);

            $createShift->save();

            $createShift = $createShift->fresh();

            $createShift->cashierShiftDetails()->insert([
                [

                    'created_at' => $now,
                    'cashier_shift_id' => $createShift->id,
                    'type' => ShiftTypeEnum::PAGI,
                    'status' => ShiftStatusEnum::BELUM_MULAI,
                ],
                [
                    'created_at' => $now,
                    'type' => ShiftTypeEnum::MALAM,
                    'cashier_shift_id' => $createShift->id,
                    'status' => ShiftStatusEnum::BELUM_MULAI,
                ]
            ]);

            $createShift->save();

            /** Create Transaction Summarize and the details */
            $createTransactionSummarize = TransactionSummarize::create([
                'cashier_shift_id' => $createShift->fresh()->id,
            ]);

            $createTransactionSummarize->save();

            $createTransactionSummarize = $createTransactionSummarize->fresh();

            $parentId = $createTransactionSummarize->id;

            $dataDetails = [
                [
                    'name' => "REKAPAN HARIAN DETAIL",
                    'ts_id' => $parentId, // Wajib manual untuk insert()
                    'created_at' => $now,
                    'invoice_type' => SalesInvoiceTypeEnum::PPN,
                    'shift_type' => ShiftTypeEnum::PAGI,
                ],
                [
                    'name' => "REKAPAN HARIAN DETAIL",
                    'ts_id' => $parentId,
                    'created_at' => $now,
                    'invoice_type' => SalesInvoiceTypeEnum::NON_PPN,
                    'shift_type' => ShiftTypeEnum::PAGI,
                ],
                [
                    'name' => "REKAPAN HARIAN DETAIL",
                    'ts_id' => $parentId,
                    'created_at' => $now,
                    'invoice_type' => SalesInvoiceTypeEnum::PPN,
                    'shift_type' => ShiftTypeEnum::MALAM,
                ],
                [
                    'name' => "REKAPAN HARIAN DETAIL",
                    'ts_id' => $parentId,
                    'created_at' => $now,
                    'invoice_type' => SalesInvoiceTypeEnum::NON_PPN,
                    'shift_type' => ShiftTypeEnum::MALAM,
                ],
            ];


            TransactionSummarizeDetail::insert($dataDetails);

            $findTransactionSummarize = TransactionSummarizeDetail::where('deleted_at', null)->whereDate('created_at', $now)->where('ts_id', $createTransactionSummarize->id)->get();

            $payloadPaymentShiftDetail = [];

            foreach ($findTransactionSummarize as $detailItem) {
                foreach ($findAllPaymentDetails as $paymentDetail) {
                    $payloadPaymentShiftDetail[] = [
                        'tsd_id' => $detailItem->id,
                        'pm_detail_id' => $paymentDetail->id,
                        'total_payment' => 0,
                        'admin_fee' => 0,
                        'total_tax' => 0
                    ];
                }
            }

            TransactionSummarizeDetailpayment::insert($payloadPaymentShiftDetail);

            DB::commit();

            return $this->successResponse("Berhasil Membuat Shift Kasir", 200, [
                'data' => $createTransactionSummarize
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage(), 500, []);

        } catch (QueryException $qeq) {
            DB::rollBack();
            if ($qeq->getCode() === '23000' || str_contains($qeq->getMessage(), 'Integrity constraint violation')) {
                return $this->errorResponse('error', 'Gagal menghapus! Data ini masih memiliki relasi aktif di tabel lain. Harap hapus relasi terkait terlebih dahulu.');
            }
            return $this->errorResponse("Internal Server Error", 500, []);
        } catch (NetworkExceptionInterface $nei) {
            DB::rollBack();
            return $this->errorResponse($nei->getMessage(), 500, []);
        }
    }

    public function detail($id)
    {
        try {
            $findShift = CashierShift::with([
                'cashierShiftDetails' => function ($query) {
                    return $query->with(['shiftTransactions', 'cashier']);
                }
            , 'transactionSummarize','createdBy'])->where('id', $id)->where('deleted_at', null)->first();

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

    public function openShift(Request $request, $id)
    {
        $request->validate([
            'initial_cash' => 'required|integer',
            'cash_drawer_amount' => 'required|string',
        ]);
        try {
            DB::beginTransaction();

            $now = Carbon::now();
            $user = auth()->user();

            $findShift = CashierShiftDetail::where('deleted_at', null)->where('id', $id)->first();
            if (!$findShift) {
                return $this->errorResponse("Cashier Shift Tidak Ditemukan", 404, []);
            }

            if ($findShift->type === ShiftTypeEnum::MALAM) {
                $findPreviousShift = CashierShiftDetail::where('deleted_at', null)->where('type', ShiftTypeEnum::PAGI)->whereDate('created_at', $now)->first();
                if ($findPreviousShift) {
                    $findShift->initial_cash_amount = $findPreviousShift->final_cash;
                }
            } else {
                $findPreviousShiftInPreviousDay = CashierShift::whereDate('created_at', $now->subDay())->where('deleted_at', null)->where('type', ShiftTypeEnum::PAGI)->first();
                if ($findPreviousShiftInPreviousDay) {
                    $findShift->initial_cash_amount = $findPreviousShiftInPreviousDay->final_cash;
                } else {
                    $findShift->initial_cash_amount = $request->get('initial_cash_amount');
                }
            }

            $findShift->status = ShiftStatusEnum::SEDANG_BERLANGSUNG;
            $findShift->shift_open_time = $now->timestamp;
            $findShift->cashier_id = $user->id;

            if ($findShift->save()) {
                return $this->successResponse("Berhasil Membuka Shift", 200, []);
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage(), 500, []);

        } catch (QueryException $qeq) {
            DB::rollBack();
            if ($qeq->getCode() === '23000' || str_contains($qeq->getMessage(), 'Integrity constraint violation')) {
                return $this->errorResponse('Gagal menghapus! Data ini masih memiliki relasi aktif di tabel lain. Harap hapus relasi terkait terlebih dahulu.', 500, []);
            }
            return $this->errorResponse("Internal Server Error", 500, []);
        } catch (NetworkExceptionInterface $nei) {
            DB::rollBack();
            return $this->errorResponse($nei->getMessage(), 500, []);
        }
    }

    public function closeShift(Request $request, $id)
    {
        $request->validate([
            'cash_in_box' => 'required|integer',
        ]);

        try {
            DB::beginTransaction();
            $now = Carbon::now();
            $user = auth()->user();


            $findShift = CashierShiftDetail::where('deleted_at', null)->where('id', $id)->first();
            $transactionSummarizeDetail = TransactionSummarize::where('deleted_at', null)->where('shift_type', $findShift->type)->where('invoice_type', SalesInvoiceTypeEnum::PPN)->first();
            $transactionSummarizeDetailNonPpn = TransactionSummarize::where('deleted_at', null)->where('shift_type', $findShift->type)->where('invoice_type', SalesInvoiceTypeEnum::NON_PPN)->first();
            if (!$findShift) {
                return $this->errorResponse("Cashier Shift Tidak Ditemukan", 404, []);
            }

            $findShift->status = ShiftStatusEnum::SELESAI;
            $findShift->shift_close_time = $now->timestamp;
            $findShift->cashier_id = $user->id;
            $findShift->cash_in_box_amount = $request->get('cash_in_box_amount');
            $findShift->cash_drawer_amount = $request->get('cash_drawer_amount');

            $cashInBox = intval($request->get('cash_in_box'));
            /**
             * 
             *  Rumus mendapatkan final cash 
             *  total 1 = total leasing +  total online + total receiveable paid + penerimaan + penerimaan non ppn + (total penjualan ppn + total non ppn)
             *  total 2 = biaya (internal fee) + debit total + transfer total + piutang total + piutang leasing total + hutang dagang + panjar sebelumnya + fee kredit plus hci
             * 
             *  total1 - total2 = difference
             * 
             *  difference - cash in box = final cash 
             * 
             */

            $nonPpnTotal = $transactionSummarizeDetailNonPpn->non_ppn_total;
            $totalNonPpn = $nonPpnTotal + $transactionSummarizeDetailNonPpn->leasing_total + $transactionSummarizeDetailNonPpn->dealer_total + $transactionSummarizeDetailNonPpn->online_Total + $transactionSummarizeDetailNonPpn->down_payment_total + $transactionSummarizeDetailNonPpn->receiveable_paid;
            $total1 = ($transactionSummarizeDetail->ppn_total + $nonPpnTotal) + $transactionSummarizeDetail->leasing_total + $transactionSummarizeDetail->online_total + $transactionSummarizeDetail->receiveable_paid + $totalNonPpn;
            $total2 = $transactionSummarizeDetail->internal_fee + $transactionSummarizeDetail->debit_total + $transactionSummarizeDetail->transfer_total + $transactionSummarizeDetail->receiveable_total + $transactionSummarizeDetail->leasing_receiveable_total + $transactionSummarizeDetail->internal_fee_total + $transactionSummarizeDetail->payable_total + $transactionSummarizeDetail->leasing_previous_down_payment_total + $transactionSummarizeDetail->leasing_fee_total;
            $finalTotal = $total1 - $total2;
            $cashDrawerTotal = $finalTotal - $cashInBox;
            $findShift->final_cash = $cashDrawerTotal;

            $findShift->save();

            DB::commit();

            return $this->successResponse("Berhasil Menutup Shift", 200, [
                'data' => $findShift->fresh()
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage(), 500, []);

        } catch (QueryException $qeq) {
            DB::rollBack();
            if ($qeq->getCode() === '23000' || str_contains($qeq->getMessage(), 'Integrity constraint violation')) {
                return $this->errorResponse('Gagal menghapus! Data ini masih memiliki relasi aktif di tabel lain. Harap hapus relasi terkait terlebih dahulu.', 500, []);
            }
            return $this->errorResponse("Internal Server Error", 500, []);
        } catch (NetworkExceptionInterface $nei) {
            DB::rollBack();
            return $this->errorResponse($nei->getMessage(), 500, []);
        }
    }

}

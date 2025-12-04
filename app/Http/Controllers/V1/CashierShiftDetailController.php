<?php

namespace App\Http\Controllers\V1;

use App\Enums\PermissionEnum;
use App\Enums\SalesInvoiceTypeEnum;
use App\Enums\ShiftStatusEnum;
use App\Enums\ShiftTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\CashierShiftDetail;
use App\Models\TransactionSummarizeDetail;
use Carbon\Carbon;
use DB;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Psr\Http\Client\NetworkExceptionInterface;


class CashierShiftDetailController extends Controller
{
    public function currentOpenShift()
    {
        try {
            $now = Carbon::now();
            $user = auth()->user();
            $findCurrentShiftDetail = CashierShiftDetail::with(['cashier'])->where('deleted_at', null)->where('cashier_id', $user->id)->where('status', ShiftStatusEnum::SEDANG_BERLANGSUNG)->whereDate('created_at', $now)->first();

            //  if(!$findCurrentShiftDetail){
            //     return $this->errorResponse("Shift Tidak Ditemukan",404,[]);
            //  }

            return $this->successResponse("Berhasil Mendapatkan Data Detail Shift", 200, ['data' => $findCurrentShiftDetail]);
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


    public function detail($id)
    {
        try {
            $user = auth()->user();

            if (!$user->hasPermissionTo(PermissionEnum::MELIHAT_DETAIL_SHIFT)) {
                return $this->errorResponse("Tidak Memiliki Hak Akses Untuk Melihat Fitur Ini", 403, []);
            }

            $findCashierShiftDetail = CashierShiftDetail::with(['cashier','operationalCosts'])->where('deleted_at', null)->where('id', $id)->first();

            if (!$findCashierShiftDetail) {
                return $this->errorResponse("Shift Tidak Ditemukan", 404, []);
            }

            $findSummarizeDetail = TransactionSummarizeDetail::with([
                'transactionSummarizeDetailsPayment' => function ($query) {
                    return $query->with(['downPaymentMethodDetail', 'otherPaymentMethodDetail', 'paymentMethodDetail']);
                }
            ])->where('deleted_at', null)->where('shift_type', $findCashierShiftDetail->type)->get();

            $data = [];

            $data = $findCashierShiftDetail;
            $data['transaction_summarize_details'] = $findSummarizeDetail;

            return $this->successResponse("Berhasil Mendapatkan Detail Shift", 200, ['data' => $data]);


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

    public function openShift(\Illuminate\Http\Request $request, $id)
    {
        $request->validate([
            'initial_cash_amount' => 'required|integer'
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
                } else {
                    $findShift->initial_cash_amount = $request->get('initial_cash_amount');
                }
            } else {
                $findPreviousShiftInPreviousDay = CashierShiftDetail::whereDate('created_at', $now->subDay())->where('deleted_at', null)->where('type', ShiftTypeEnum::PAGI)->first();
                if ($findPreviousShiftInPreviousDay) {
                    $findShift->initial_cash_amount = $findPreviousShiftInPreviousDay->final_cash;
                } else {
                    $findShift->initial_cash_amount = $request->get('initial_cash_amount');
                }
            }

            if ($request->has('cash_drawer_amount')) {
                $findShift->cash_drawer_amount = $request->get('cash_drawer_amount');
            }

            if ($request->has('cash_in_box_amount')) {
                $findShift->cash_in_box_amount = $request->get('cash_in_box_amount');
            }


            $findShift->status = ShiftStatusEnum::SEDANG_BERLANGSUNG;
            $findShift->shift_open_time = $now;
            $findShift->cashier_id = $user->id;

            $findShift->save();
            DB::commit();
            return $this->successResponse("Berhasil Membuka Shift", 200, [
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

    public function closeShift(Request $request, $id)
    {
        $request->validate([
            'cash_drawer_amount' => 'required',
            'initial_cash_amount' => 'required',
            'cash_in_box_amount' => 'required'
        ]);


        try {
            DB::beginTransaction();
            $now = Carbon::now();
            $user = auth()->user();

            $findShift = CashierShiftDetail::where('deleted_at', null)->where('id', $id)->first();

            $transactionSummarizeDetail = TransactionSummarizeDetail::where('deleted_at', null)->where('shift_type', $findShift->type)->where('invoice_type', SalesInvoiceTypeEnum::PPN)->first();
            $transactionSummarizeDetailNonPpn = TransactionSummarizeDetail::where('deleted_at', null)->where('shift_type', $findShift->type)->where('invoice_type', SalesInvoiceTypeEnum::NON_PPN)->first();
            if (!$findShift) {
                return $this->errorResponse("Cashier Shift Tidak Ditemukan", 404, []);
            }

            if($findShift->status === 'SELESAI'){
                return $this->errorResponse("Shift Sudah Diselesaikan", 400, []);
            }

            $findShift->status = ShiftStatusEnum::SELESAI;
            $findShift->shift_close_time = $now;
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

    public function update(Request $request, $id)
    {
        $request->validate([
            'cash_b'
        ]);
        try {
            DB::beginTransaction();

            $findShiftDetail = CashierShiftDetail::where('deleted_at', null)->where('id', $id)->First();
            if (!$findShiftDetail) {
                return $this->errorResponse("Shift Detail Tidak Ditemukan", 404, []);
            }

            $findShiftDetail->cash_drawer_amount = $request->get('cash_drawer_amount');
            $findShiftDetail->cash_in_box_amount = $request->get('cash_in_box_amount');

            $findShiftDetail->save();

            DB::commit();

            return $this->successResponse("Berhasil Mengedit Detail Shift kasir", 200, [
                'data' => $findShiftDetail->fresh()
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

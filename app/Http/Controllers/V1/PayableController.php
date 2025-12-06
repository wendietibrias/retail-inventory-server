<?php

namespace App\Http\Controllers\V1;

use App\Enums\PayableStatusEnum;
use App\Helper\UpdateTransactionSummarize;
use App\Http\Controllers\Controller;
use App\Models\Payable;
use Carbon\Carbon;
use DB;
use Exception;
use Illuminate\Database\QueryException;
use Psr\Http\Client\NetworkExceptionInterface;
use Request;


use function intval;

class PayableController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'page' => 'required|integer',
            'per_page' => 'required|integer',
            'sort_by' => 'required|string',
            'order_by' => 'required|string'
        ]);

        try {
            $payable = Payable::where('deleted_at', null);

            if ($request->has('search')) {
                $search = $request->get('search');
                $payable->where(function ($query) {});
            }

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

    public function create(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'description' => 'string',
            'sub_total' => 'required|integer',
            'grand_total' => 'required|integer'
        ]);

        try {
            DB::beginTransaction();
            $user = auth()->user();
            $now = Carbon::now();

            $year = $now->year;
            $month = $now->month;

            $latestPayableCount = Payable::count();
            $payableCount = $latestPayableCount < 1 ? 1 : $latestPayableCount + 1;
            $payableCode = "PAYABLE/$year$month/$payableCount";

            $createPayable = Payable::create([
                'name' => $request->get('name'),
                'description' => $request->get('description'),
                'code' =>$payableCode,
                'tax_amount' => intval($request->get('tax_amount')),
                'sub_total' => intval($request->get('grand_total')),
                'grand_total' => intval($request->get('grand_total')),
            ]);

            $createPayable->save();

            $payableDetails = [];

            foreach ($request->get('payableDetails') as $payableDetail) {
                $payableDetails[] = [
                    'sub_total' => $payableDetail['sub_total'],
                    'name' => $payableDetail['name'],
                    'payable_id' => $createPayable->fresh()->id,
                    'user_id' => $user->id,
                ];
            }

            $createPayable->payableDetails()->insert($payableDetails);

            $createPayable = $createPayable->fresh();

            UpdateTransactionSummarize::updatePayable([
                'payable_total'=>$createPayable->grand_total,
                'payable_date'=> $createPayable->payable_date,
                'invoice_type'=>$request->get('invoice_type'),
                'shift_type'=>$request->get('shift_type')
            ]);

            DB::commit();

            return $this->successResponse("Berhasil Menambahkan Hutang Dagang", 200, [
                'data' => $createPayable->fresh()
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage(), $e->getCode(), []);

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

    public function delete($id)
    {
        try {
            $findPayable = Payable::with(['payableDetails'])->where('deleted_at', null)->where('id', $id)->first();
            if (!$findPayable) {
                return $this->errorResponse("Hutang Dagang Tidak Ditemukan", 404, []);
            }

            $findPayable->status = PayableStatusEnum::DITOLAK;

            if ($findPayable->save()) {
                return $this->successResponse("Berhasil Mendapatkan Data Hutang Dagang", 200, [
                    'data' => $findPayable
                ]);
            }

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

    public function update($id, Request $request)
    {
    }

    public function detail($id)
    {
        try {
            $findPayable = Payable::with(['payableDetails'])->where('deleted_at', null)->where('id', $id)->first();
            if (!$findPayable) {
                return $this->errorResponse("Hutang Dagang Tidak Ditemukan", 404, []);
            }

            return $this->successResponse("Berhasil Mendapatkan Data Hutang Dagang", 200, [
                'data' => $findPayable
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

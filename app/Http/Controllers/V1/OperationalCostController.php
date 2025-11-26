<?php

namespace App\Http\Controllers\V1;

use App\Enums\OperationalCostStatusEnum;
use App\Enums\SalesInvoiceStatusEnum;
use App\Enums\SalesInvoiceTypeEnum;
use App\Helper\UpdateTransactionSummarize;
use App\Http\Controllers\Controller;
use App\Models\CashierShiftDetail;
use App\Models\OperationalCost;
use DB;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Psr\Http\Client\NetworkExceptionInterface;

class OperationalCostController extends Controller
{
    public function create($id, Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'cost_fee' => 'required|integer',
            'description' => 'string',
            'cashier_shift_detail_id' =>'required|integer',
        ]);

        try {
            DB::beginTransaction();

            $user = auth()->user();
            $cashierShiftDetail = CashierShiftDetail::where('deleted_at', null)->where('id', $id);
            if (!$cashierShiftDetail) {
                return $this->errorResponse("Shift Tidak Ditemukan", 404, []);
            }

            $createOperationalCost = OperationalCost::create([
                'cashier_shift_detail_id' => $cashierShiftDetail->id,
                'created_by_id' => $user->id,
                'name' => $request->get('name'),
                'description' => $request->get('description'),
                'cost_fee' => intval($request->get('cost_fee'))
            ]);

            $createOperationalCost->save();

            DB::commit();

            return $this->successResponse("Berhasil Menambahkan Biaya Operasional", 200, []);

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

    public function updateStatus($id,Request $request){
        $request->validate([
            'status'=> 'required|string',
            'cashier_shift_detail_id'=>'required|integer'
        ]);
        
        try {
         DB::beginTransaction();

         $cashierShiftDetailId = $request->get('cashier_shift_detail_id');
         $status = $request->get('status');

         $findCashierShiftDetail = CashierShiftDetail::where('deleted_at',null)->where('id',$cashierShiftDetailId)->first();
         $findOperational = OperationalCost::where('deleted_at',null)->where('id',$id)->first();

         if(!$findOperational){
            return $this->errorResponse("Tidak Menemukan Biaya Operational",404,[]);
         }

         if(!$findCashierShiftDetail){
            return $this->errorResponse("Shift Kasir Tidak Ditemukan",404,[]);
         }
         

         if($status === OperationalCostStatusEnum::DISETUJUI){
            UpdateTransactionSummarize::updateInternalFee([
                'internal_fee',
                'invoice_type'=>SalesInvoiceTypeEnum::PPN,
                'shift_type'=>$findCashierShiftDetail->type,
            ]);
            $findOperational->status = OperationalCostStatusEnum::DISETUJUI;
         } else {
            $findOperational->status = OperationalCostStatusEnum::DITOLAK;
         }

         $findOperational->save();

         DB::commit();

         return $this->successResponse("Berhasil Mengubah Status Biaya Operational",200,[
            'data'=>$findOperational->fresh()
         ]);

        }catch (Exception $e) {
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

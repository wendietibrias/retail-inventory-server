<?php

namespace App\Http\Controllers\V1;

use App\Enums\ReceiveableStatusEnum;
use App\Enums\ReceiveableTypeEnum;
use App\Enums\SalesInvoiceStatusEnum;
use App\Helper\UpdateTransactionSummarize;
use App\Http\Controllers\Controller;
use App\Models\CashierShiftDetail;
use App\Models\PaymentMethod;
use App\Models\Receiveable;
use App\Models\SalesInvoice;
use App\Models\ShiftTransaction;
use DB;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

use Psr\Http\Client\NetworkExceptionInterface;
use function strtolower;
use function intval;

class ShiftTransactionController extends Controller
{
    public function indexByCashierShiftDetailId($id,Request $request){
        try {
            $perPage = $request->get('per_page');
            $shiftTransaction = ShiftTransaction::with(['paymentMethodDetail', 'otherPaymentMethodDetail', 'downPaymentMethodDetail'])->where('deleted_at',null)->where('cs_detail_id',$id);

            if($request->has('shift_type')){
                $shiftType = $request->get('shift_type');
                $shiftTransaction->whereHas('cashierShiftDetail', function($query) use ($shiftType){
                    return $query->where('type', $shiftType);
                });
            }

            return $this->successResponse("Berhasil Mendapaktna Data Transaksi Shift", 200, [
                'items'=>$shiftTransaction->paginate($perPage)
            ]);

        }  catch (Exception $e) {
         DB::rollBack();
         return $this->errorResponse($e->getMessage(), 500, []);
      } catch (QueryException $eq) {
         DB::rollBack();
         return $this->errorResponse($eq->getMessage(), 500, []);
      } catch (NetworkExceptionInterface $nei) {
         DB::rollBack();
         return $this->errorResponse($nei->getMessage(), 500, []);
      }
    }

    public function create(Request $request, $id)
    {
        $request->validate([
            'sales_invoice_id' => 'required|integer',
            'paid_amount' => 'integer',
            'payment_method_id' => 'required|integer',
            'payment_method_detail_id' => 'integer',
            'down_payment_method_detail_id' => 'integer',
            'other_payment_method_detail_id' => 'integer',
            'other_paid_amount' => 'integer',
            'down_payment_amount' => 'integer',
            'admin_fee_amount' => 'integer',
            'tax_amount' => 'integer',
            'cashier_shift_detail_id' => 'integer',
            'leasing_id' => 'integer',
        ]);

        try {
            DB::beginTransaction();

            $user = auth()->user();

            $findShiftDetail = CashierShiftDetail::where('deleted_at',null)->where('id',$id)->first();
            $findPaymentMethod = PaymentMethod::where('deleted_at', null)->where('id', $request->get('payment_method_id'))->first();
            $findSI = SalesInvoice::with(['salesInvoiceDetails'])->where('deleted_at', null)->where('id', $request->get('sales_invoice_id'));
            $createReceiveable = null;

            $shiftTransaction = new ShiftTransaction;

            if (!$findSI) {
                return $this->errorResponse("Sales Invoice Tidak Ditemukan", 404, []);
            }

            $shiftTransaction->sales_invoice_id = $findSI->get('id');
            $shiftTransaction->dpm_detail_id = $request->get('dpm_detail_id');
            $shiftTransaction->opm_detail_id = $request->get('opm_detail_id');
            $shiftTransaction->pm_detail_id = $request->get('pm_detail_id');
            $shiftTransaction->admin_fee = intval($request->get('admin_fee_amount'));

            $downPaymentTotal = $request->get('down_payment_amount');
            $otherPaymentTotal = $request->get('other_paid_amount');
            $paidAmount = $request->get('paid_amount');
            $totalPayment = $paidAmount + $otherPaymentTotal;


            if ($request->has('dpm_detail_id')) {
                $downPaymentTotal = $request->get('down_payment_amount');
            }

            if ($request->has('opm_detail_id')) {
                $otherPaymentTotal = $request->get('other_payment_amount');
            }

            $shiftTransaction->cashier_detail_id = $id;
            $shiftTransaction->paid_amount = $paidAmount;
            $shiftTransaction->down_payment_amount =$downPaymentTotal;
            $shiftTransaction->other_payment_amount = $otherPaymentTotal;
            $shiftTransaction->total_paid_amount =$totalPayment;
            $shiftTransaction->admin_fee_amount = $request->get('admin_fee_amount');

            if (strtolower($findPaymentMethod->get('name')) === 'kredit' || strtolower($findPaymentMethod->get('name')) === 'credit') {
                // harusnya bakalan jadi piutang
                if ($findSI->get('status') === SalesInvoiceStatusEnum::DISETUJUI_MENJADI_PIUTANG) {
                    $shiftTransaction->leasing_id =$request->get('leasing_id');
                    $createReceiveable = Receiveable::create([
                        'code' => $findSI->get('code'),
                        'sales_invoice_id' => $findSI->get('id'),
                        'type' => ReceiveableTypeEnum::PIUTANG,
                        'created_by_id' => $user->get('id'),
                        'receiveable_total' => $findSI->get('grand_total') - $downPaymentTotal,
                        'receiveable_left' => $findSI->get('grand_total') - $downPaymentTotal,
                        'cashier_id' => $user->get('id'),
                        'status' => ReceiveableStatusEnum::BELUM_LUNAS,
                        'paid_receiveable' => 0
                    ]);
                }
            } else if (strtolower($findPaymentMethod->get('name')) === 'leasing') {
                //harusnya bakalan jadi piutang juga
                if ($findSI->get('status') === SalesInvoiceStatusEnum::DISETUJUI_MENJADI_PIUTANG) {
                    $createReceiveable = Receiveable::create([
                        'code' => $findSI->get('code'),
                        'sales_invoice_id' => $findSI->get('id'),
                        'type' => ReceiveableTypeEnum::PIUTANG_LEASING,
                        'created_by_id' => $user->get('id'),
                        'receiveable_total' => $findSI->get('grand_total') - $downPaymentTotal,
                        'receiveable_left' => $findSI->get('grand_total') - $downPaymentTotal,
                        'cashier_id' => $user->get('id'),
                        'status' => ReceiveableStatusEnum::BELUM_LUNAS,
                        'paid_receiveable' => 0
                    ]);
                }
            }

            if($createReceiveable){
                $createReceiveable->save();
            }

            $shiftTransaction->save();
            $shiftTransaction = $shiftTransaction->fresh();

           UpdateTransactionSummarize::updateSummarize([
             'shift_type'=> $findShiftDetail->type,
             'sales_invoice' => $findSI,
             'invoice_type'=>$findSI->type,
             'payment_method_id'=> $request->get('payment_method_detail_id'),
             'paid_amount'=>$paidAmount,
             'leasing_fee'=>intval($request->get('admin_fee_amount')),
             'other_payment_amount'=> $otherPaymentTotal,
             'other_payment_method_detail_id'=> $request->get('other_payment_method_detail_id'),
             'down_payment_method_detail_id'=> $request->get('other_payment_method_detail_id'),
             'down_payment_amount'=>$downPaymentTotal,
           ]);

           if($downPaymentTotal < 1) {
              $findSI->grand_total_left -= ($otherPaymentTotal + $paidAmount);
             if($findSI->grand_total_left < 1){
                 $findSI->status = SalesInvoiceStatusEnum::DIBAYARKAN_KESELURUHAN;
             } else {
                 $findSI->status = SalesInvoiceStatusEnum::DIBAYARKAN_PARSIAL;
             }
             $findSI->paid_amount += ($paidAmount + $otherPaymentTotal);

           } else {
              $findSI->status = SalesInvoiceStatusEnum::DIBAYARKAN_PARSIAL;
              $findSI->grand_total_left -= $downPaymentTotal;
              $findSI->paid_amount += $downPaymentTotal;
           }

           $findSI->is_in_paid = true;

           $findSI->save();

            DB::commit();

            return $this->successResponse("Berhasil Membuat Transaksi", 200, [
                'data'=>$shiftTransaction->fresh()
            ]);
            
        } catch (Exception $e) {
         DB::rollBack();
         return $this->errorResponse($e->getMessage(), 500, []);
      } catch (QueryException $eq) {
         DB::rollBack();
         return $this->errorResponse($eq->getMessage(), 500, []);
      } catch (NetworkExceptionInterface $nei) {
         DB::rollBack();
         return $this->errorResponse($nei->getMessage(), 500, []);
      }
    }

}

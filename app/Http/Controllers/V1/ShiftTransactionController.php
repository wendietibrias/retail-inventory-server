<?php

namespace App\Http\Controllers\V1;

use App\Enums\ReceiveableStatusEnum;
use App\Enums\ReceiveableTypeEnum;
use App\Enums\SalesInvoiceStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Models\Receiveable;
use App\Models\SalesInvoice;
use App\Models\ShiftTransaction;
use DB;
use Exception;
use Illuminate\Http\Request;

use function strtolower;

class ShiftTransactionController extends Controller
{
    public function index()
    {
    }
    public function create(Request $request, $id)
    {
        $request->validate([
            'sales_invoice_id' => 'required|integer',
            'paid_amount' => 'integer',
            'payment_method_id' => 'required|integer',
            'pm_detail_id' => 'integer',
            'dpm_detail_id' => 'integer',
            'opm_detail_id' => 'integer',
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

            $findPaymentMethod = PaymentMethod::where('deleted_at', null)->where('id', $request->get('payment_method_id'))->first();
            $findSI = SalesInvoice::where('deleted_at', null)->where('id', $request->get('sales_invoice_id'));
            $createReceiveable = null;

            $shiftTransaction = new ShiftTransaction;

            if (!$findSI) {
                return $this->errorResponse("Sales Invoice Tidak Ditemukan", 404, []);
            }

            $shiftTransaction->sales_invoice_id = $findSI->get('id');
            $shiftTransaction->dpm_detail_id = $request->get('dpm_detail_id');
            $shiftTransaction->opm_detail_id = $request->get('opm_detail_id');
            $shiftTransaction->pm_detail_id = $request->get('pm_detail_id');

            $downPaymentTotal = 0;
            $otherPaymentTotal = 0;
            $paidAmount = 0;
            $totalPayment = 0;

            $paidAmount = $request->get('paid_amount');

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
            } else {

            }

            $shiftTransaction->save();

            DB::commit();

            return $this->successResponse("Berhasil Membuat Transaksi", 200, [
                'data'=>$shiftTransaction->fresh()
            ]);
            
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage(), 500, []);
        }
    }

    public function update()
    {
    }

    public function detail()
    {
    }
}

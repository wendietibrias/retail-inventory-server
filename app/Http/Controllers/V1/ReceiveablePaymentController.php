<?php

namespace App\Http\Controllers\V1;

use App\Enums\PermissionEnum;
use App\Enums\ReceiveablePaymentStatusEnum;
use App\Enums\ReceiveableStatusEnum;
use App\Helper\UpdateTransactionSummarize;
use App\Http\Controllers\Controller;
use App\Models\Receiveable;
use App\Models\ReceiveablePayment;
use App\Models\SalesInvoice;
use Carbon\Carbon;
use DB;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Psr\Http\Client\NetworkExceptionInterface;

use function intval;

class ReceiveablePaymentController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page');
            $receiveablePayment = ReceiveablePayment::with([])->where('deleted_at', null);

            $user = auth()->user();

            if ($user->hasPermissionTo(PermissionEnum::MELIHAT_PEMBAYARAN_PIUTANG) && !$request->has('is_public')) {
                return $this->errorResponse("Tidak Memiliki Hak Untuk Melihat Fitur Ini");
            }

            if ($request->has('search')) {
                $search = $request->get('search');
                $receiveablePayment->where(function ($query) use ($search) {
                    return $query->where('code', 'like', "%$search%");
                });
            }

            return $this->successResponse("Berhasil Mendapatkan Data Pembayaran Piutang", 200, [
                'items' => $receiveablePayment->paginate($perPage)
            ]);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500, []);

        } catch (QueryException $qeq) {
            if ($qeq->getCode() === '23000' || str_contains($qeq->getMessage(), 'Integrity constraint violation')) {
                return $this->errorResponse('Gagal menghapus! Data ini masih memiliki relasi aktif di tabel lain. Harap hapus relasi terkait terlebih dahulu.', 500, []);
            }
            return $this->errorResponse("Internal Server Error", 500, []);
        } catch (NetworkExceptionInterface $nei) {
            return $this->errorResponse($nei->getMessage(), 500, []);
        }
    }

    public function indexByReceiveableId($id, Request $request)
    {
        try {
            $perPage = $request->get('per_page');
            $receiveablePayment = ReceiveablePayment::with([])->where('deleted_at', null)->where('receiveable_id', $id);

            $user = auth()->user();

            if ($user->hasPermissionTo(PermissionEnum::MELIHAT_PIUTANG) && !$request->has('is_public')) {
                return $this->errorResponse("Tidak Memiliki Hak Untuk Melihat Fitur Ini");
            }


            if ($request->has('search')) {
                $search = $request->get('search');
                $receiveablePayment->where(function ($query) use ($search) {
                    return $query->where('code', 'like', "%$search%");
                });
            }

            return $this->successResponse("Berhasil Mendapatkan Data Pembayaran Piutang", 200, [
                'items' => $receiveablePayment->paginate($perPage)
            ]);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500, []);

        } catch (QueryException $qeq) {
            if ($qeq->getCode() === '23000' || str_contains($qeq->getMessage(), 'Integrity constraint violation')) {
                return $this->errorResponse('Gagal menghapus! Data ini masih memiliki relasi aktif di tabel lain. Harap hapus relasi terkait terlebih dahulu.', 500, []);
            }
            return $this->errorResponse("Internal Server Error", 500, []);
        } catch (NetworkExceptionInterface $nei) {
            return $this->errorResponse($nei->getMessage(), 500, []);
        }
    }

    public function create(Request $request)
    {
        $request->validate([
            'payment_method_detail_id' => 'integer',
            'other_payment_method_id' => 'integer',
            'paid_amount' => 'required|integer',
            'sales_invoice_id' => 'required|integer',
            'description' => 'string',
        ]);

        $user = auth()->user();

        if ($user->hasPermissionTo(PermissionEnum::MEMBUAT_PEMBAYARAN_PIUTANG)) {
            return $this->errorResponse("Tidak Memiliki Hak Untuk Melihat Fitur Ini");
        }


        try {
            DB::beginTransaction();

            $now = Carbon::now();
            $user = auth()->user();
            $salesinvoiceId = $request->get('sales_invoice_id');
            $findSalesInvoice = SalesInvoice::where('deleted_at', null)->where('id', $salesinvoiceId)->first();
            $findReceiveable = Receiveable::where('deleted_at', null)->where('sales_invoice_id', $salesinvoiceId)->first();

            if (!$findSalesInvoice) {
                return $this->errorResponse("Faktur Penjualan Tidak Ditemukan", 404, []);
            }

            if (!$findReceiveable) {
                return $this->errorResponse("Piutang Tidak Ditemukan", 404, []);
            }

            $paymentMethodDetail = $request->get('payment_method_detail_id');
            $otherPaymentMethodDetail = $request->get('other_payment_method_detail_id');

            $paidAmount = intval($request->get('paid_amount'));
            $otherPaidAmount = intval($request->get('other_paid_amount'));

            $findReceiveable->paid_receiveable += $paidAmount;
            $findReceiveable->receiveable_left -= $paidAmount;

            if (intval($otherPaidAmount) > 0) {
                $findReceiveable->paid_receiveable += $otherPaidAmount;
                $findReceiveable->receiveable_left -= $otherPaidAmount;
            }

            if (intval($findReceiveable->receiveable_left) < 1) {
                $findReceiveable->receiveable_left = 0;
            }

            $createReceiveablePayment = ReceiveablePayment::create([
                'paid_amount' => $paidAmount,
                'other_paid_amount' => $otherPaidAmount,
                'create_by_id' => $user->id,
                'paid_date' => $now,
                'receiveable_id' => $findReceiveable->id,
                'description' => $request->get('description'),
                'pm_detail_id' => $paymentMethodDetail,
                'opm_detail_id' => $otherPaymentMethodDetail
            ]);

            $createReceiveablePayment->save();

            UpdateTransactionSummarize::updateReceiveable([
                'sales_invoice' => $findSalesInvoice,
                'payment_method_id' => $paymentMethodDetail->id,
                'other_payment_method_id' => $otherPaymentMethodDetail->id,
                'paid_amount' => $paidAmount,
                'other_paid_amount' => $otherPaidAmount,
            ]);

            DB::commit();

            return $this->successResponse("Berhasil Membuat Pembayaran Piutang", 200, []);


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

    public function changeStatus($id, Request $request)
    {

        try {
          DB::beginTransaction();

          $findReceiveablePayment = ReceiveablePayment::where('deleted_at',null)->where('id',$id)->first();
          $findReceiveable = Receiveable::where('deleted_at', null)->where('id',$findReceiveablePayment->receiveable_id);

          if(!$findReceiveablePayment){
            return $this->errorResponse("Pembayaran Piutang Tidak Ditemukan",404,[]);
          }

          if(!$findReceiveable){
            return $this->errorResponse("Piutang Tidak Ditemukan",404,[]);
          }

          if($request->has('status')){
            if($request->get('status') === 'DISETUJUI'){
                $findReceiveablePayment->status = ReceiveablePaymentStatusEnum::DISETUJUI;

                $total = $findReceiveablePayment->paid_amount + $findReceiveablePayment->other_paid_amount;

                $findReceiveable->paid_receiveable += $findReceiveablePayment->paid_amount;
                if($findReceiveablePayment->other_paid_amount > 1) {
                    $findReceiveable->paid_receiveable += $findReceiveablePayment->other_paid_amount;
                }

                $findReceiveable->receiveable_left -= $total;

                if($findReceiveable->receiveable_left ==0 || $findReceiveable->receiveable_left < 1) {
                    $findReceiveable->status = ReceiveableStatusEnum::LUNAS;
                } else {
                    $findReceiveable->status =ReceiveableStatusEnum::DIBAYARKAN_PARSIAL;
                }
            }
          }

          $findReceiveable->save();
          $findReceiveablePayment->save();

          DB::commit();

          return $this->successResponse("Berhasil Mengubah Status Pembayaran Piutang", 200, []);

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

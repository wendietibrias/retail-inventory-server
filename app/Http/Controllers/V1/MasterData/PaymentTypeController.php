<?php

namespace App\Http\Controllers\V1;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Models\PaymentType;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Psr\Http\Client\NetworkExceptionInterface;

class PaymentTypeController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'page' => 'required|integer',
            'per_page' => 'required|integer',
        ]);

        try {
            $user = auth()->user();

            if (!$user->hasPermissionTo(PermissionEnum::MELIHAT_TIPE_PEMBAYARAN) && !$request->has('is_public')) {
                return $this->errorResponse("Tidak Permission Untuk Melakukan Aksi Ini", 403, []);
            }
            $page = $request->get('page');
            $perPage = $request->get('perPage');

            $findAllPaymentType = PaymentType::with(['paymentMethod'])->where('deleted_at', null);

            if ($request->has('search')) {
                $findAllPaymentType->where(function ($query) use ($request) {
                    return $query->orWhere('name', 'like', "%$request->get('search')%")
                        ->orWhere('code', 'like', "%$request->get('search')%");
                });
            }

            if ($request->has('payment_method_id')) {
                $findAllPaymentType->where('payment_method_id', $request->get('payment_method_id'));
            }

            if ($request->has('sortBy') && $request->has('orderBy')) {
                $findAllPaymentType->orderBy($request->get('orderBy'), $request->get('sortBy'));
            }

            return $this->successResponse("Berhasil Mendapatkan Data Payment Type", 200, [
                'items' => $findAllPaymentType->paginate($perPage),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500, []);

        } catch (QueryException $qeq) {
            if ($qeq->getCode() === '23000' || str_contains($qeq->getMessage(), 'Integrity constraint violation')) {
                return $this->errorResponse('Gagal menghapus! Data ini masih memiliki relasi aktif di tabel lain. Harap hapus relasi terkait terlebih dahulu.',500,[]);
            }
            return $this->errorResponse("Internal Server Error", 500, []);
        } catch (NetworkExceptionInterface $nei) {
            return $this->errorResponse($nei->getMessage(), 500, []);
        }
    }

    public function create(Request $request)
    {

        try {
            $request->validate([
                'name' => 'required|string',
                'description' => 'string',
                'payment_method_id' => 'required|integer'
            ]);

            $user = auth()->user();

            if (!$user->hasPermissionTo(PermissionEnum::MEMBUAT_TIPE_PEMBAYARAN)) {
                return $this->errorResponse("Tidak Permission Untuk Melakukan Aksi Ini", 403, []);
            }

            $PaymentType = new PaymentType;

            $PaymentType->name = $request->get('name');
            // $PaymentType->description = $request->get('description');
            $PaymentType->payment_method_id = $request->get('payment_method_id');

            if ($PaymentType->save()) {
                return $this->successResponse("Berhasil Menambahkan Payment Type", 200, [
                    'data' => $PaymentType
                ]);
            }

        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500, []);

        } catch (QueryException $qeq) {
            if ($qeq->getCode() === '23000' || str_contains($qeq->getMessage(), 'Integrity constraint violation')) {
                return $this->errorResponse( 'Gagal menghapus! Data ini masih memiliki relasi aktif di tabel lain. Harap hapus relasi terkait terlebih dahulu.',500,[]);
            }
            return $this->errorResponse("Internal Server Error", 500, []);
        } catch (NetworkExceptionInterface $nei) {
            return $this->errorResponse($nei->getMessage(), 500, []);
        }
    }

    public function destroy($id)
    {
        try {
            $user = auth()->user();

            if (!$user->hasPermissionTo(PermissionEnum::MENGHAPUS_TIPE_PEMBAYARAN)) {
                return $this->errorResponse("Tidak Permission Untuk Melakukan Aksi Ini", 403, []);
            }
            $findPaymentType = PaymentType::find($id);
            if (!$findPaymentType) {
                return $this->errorResponse("Payment Type Tidak Ditemukan");
            }

            if ($findPaymentType->delete()) {
                return $this->successResponse("Berhasil Menghapus Payment Type", 200, [
                    'data' => $findPaymentType
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

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string',
            'description' => 'string',
            'payment_method_id'=>'required|integer'
        ]);

        try {
            $user = auth()->user();

            if (!$user->hasPermissionTo(PermissionEnum::MENGEDIT_TIPE_PEMBAYARAN)) {
                return $this->errorResponse("Tidak Permission Untuk Melakukan Aksi Ini", 403, []);
            }
            $findPaymentType = PaymentType::find($id);
            if (!$findPaymentType) {
                return $this->errorResponse("Payment Type Tidak Ditemukan");
            }

            $findPaymentType->name = $request->get('name');
            $findPaymentType->payment_method_id = $request->get('payment_method_id');
            // $findPaymentType->description = $request->get('description');

            if ($findPaymentType->save()) {
                return $this->successResponse("Berhasil Mengedit Payment Type", 200, [
                    'data' => $findPaymentType
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

    public function detail($id)
    {
        try {
            $user = auth()->user();

            if (!$user->hasPermissionTo(PermissionEnum::MELIHAT_DETAIL_TIPE_PEMBAYARAN)) {
                return $this->errorResponse("Tidak Permission Untuk Melakukan Aksi Ini", 403, []);
            }
            $findPaymentType = PaymentType::find($id);
            if (!$findPaymentType) {
                return $this->errorResponse("Payment Type Tidak Ditemukan");
            }

            return $this->successResponse("Berhasil Mendapatkan Data Payment Type", 200, [
                'data' => $findPaymentType
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

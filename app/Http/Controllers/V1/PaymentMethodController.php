<?php

namespace App\Http\Controllers\V1;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Psr\Http\Client\NetworkExceptionInterface;

class PaymentMethodController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'page' => 'required|integer',
            'per_page' => 'required|integer',
            'search' => 'string',
            'is_public' => 'boolean'
        ]);

        try {
            $user = auth()->user();

            if (!$user->hasPermissionTo(PermissionEnum::MELIHAT_METODE_PEMBAYARAN) && !$request->has('is_public')) {
                return $this->errorResponse("Tidak Permission Untuk Melakukan Aksi Ini", 403, []);
            }

            $page = $request->get('page');
            $perPage = $request->get('perPage');

            $findAllPaymentMethod = PaymentMethod::with(['paymentTypes'])->where('deleted_at', null);

            if ($request->has('search')) {
                $findAllPaymentMethod->where(function ($query) use ($request) {
                    return $query->orWhere('name', 'like', "%$request->get('search')%")
                        ->orWhere('code', 'like', "%$request->get('search')%");
                });
            }

            if ($request->has('sortBy') && $request->has('orderBy')) {
                $findAllPaymentMethod->orderBy($request->get('orderBy'), $request->get('sortBy'));
            }

            if($request->has('is_public') && ($request->get('is_public') === true ||$request->get('is_public') === 'true')){
                return $this->successResponse("Berhasil Mendapatkan Payment Method", 200, [
                    'data' => $findAllPaymentMethod->get()
                ]);
            }

            return $this->successResponse("Berhasil Mendapatkan Data Payment Method", 200, [
                'items' => $findAllPaymentMethod->paginate($perPage),
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

    public function create(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'description'=>'string'
        ]);

        try {
            $user = auth()->user();

            if (!$user->hasPermissionTo(PermissionEnum::MEMBUAT_METODE_PEMBAYARAN)) {
                return $this->errorResponse("Tidak Permission Untuk Melakukan Aksi Ini", 403, []);
            }

            $paymentMethod = new PaymentMethod;

            $paymentMethod->name = $request->get('name');
            $paymentMethod->description = $request->get('description');

            if ($paymentMethod->save()) {
                return $this->successResponse("Berhasil Menambahkan Payment Method", 200, [
                    'data' => $paymentMethod
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

    public function destroy($id)
    {
        try {
            $user = auth()->user();

            if (!$user->hasPermissionTo(PermissionEnum::MENGHAPUS_METODE_PEMBAYARAN)) {
                return $this->errorResponse("Tidak Permission Untuk Melakukan Aksi Ini", 403, []);
            }
            $findPaymentMethod = PaymentMethod::find($id);
            if (!$findPaymentMethod) {
                return $this->errorResponse("Leasing Tidak Ditemukan");
            }

            if ($findPaymentMethod->delete()) {
                return $this->successResponse("Berhasil Menghapus PaymentMethod", 200, [
                    'data' => $findPaymentMethod
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
        ]);

        try {
            $user = auth()->user();

            if (!$user->hasPermissionTo(PermissionEnum::MENGEDIT_METODE_PEMBAYARAN)) {
                return $this->errorResponse("Tidak Permission Untuk Melakukan Aksi Ini", 403, []);
            }
            $findPaymentMethod = PaymentMethod::find($id);
            if (!$findPaymentMethod) {
                return $this->errorResponse("Leasing Tidak Ditemukan");
            }

            $findPaymentMethod->name = $request->get('name');
            $findPaymentMethod->description = $request->get('description');

            if ($findPaymentMethod->save()) {
                return $this->successResponse("Berhasil Mengedit Payment Method", 200, [
                    'data' => $findPaymentMethod
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

            if (!$user->hasPermissionTo(PermissionEnum::MELIHAT_DETAIL_METODE_PEMBAYARAN)) {
                return $this->errorResponse("Tidak Permission Untuk Melakukan Aksi Ini", 403, []);
            }
            $findPaymentMethod = PaymentMethod::find($id);
            if (!$findPaymentMethod) {
                return $this->errorResponse("Payment Method Tidak Ditemukan");
            }

            return $this->successResponse("Berhasil Mendapatkan Data Payment Method", 200, [
                'data' => $findPaymentMethod
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

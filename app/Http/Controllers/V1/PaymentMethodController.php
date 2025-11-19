<?php

namespace App\Http\Controllers\V1;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Exception;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'page' => 'required|integer',
            'perPage' => 'required|integer',
            'search' => 'string',
            'is_public'=>'boolean'
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

            return $this->successResponse("Berhasil Mendapatkan Data Payment Method", 200, [
                'items' => $findAllPaymentMethod->paginate($perPage),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500, []);
        }
    }

    public function create(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'code' => 'required|string',
            'description' => 'string',
            'payment_method_id' => 'required|integer',
        ]);

        try {
            $user = auth()->user();

            if (!$user->hasPermissionTo(PermissionEnum::MEMBUAT_METODE_PEMBAYARAN)) {
                return $this->errorResponse("Tidak Permission Untuk Melakukan Aksi Ini", 403, []);
            }

            $paymentMethod = new PaymentMethod;

            $paymentMethod->name = $request->get('name');
            $paymentMethod->code = $request->get('code');
            $paymentMethod->payment_method_id = $request->get('payment_method_id');
            $paymentMethod->description = $request->get('description');

            if ($paymentMethod->save()) {
                return $this->successResponse("Berhasil Menambahkan Payment Method", 200, [
                    'data' => $paymentMethod
                ]);
            }

        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode(), []);
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
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string',
            'code' => 'required|string',
            'description' => 'string',
            'payment_method_id' => 'required|integer'
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
            $findPaymentMethod->code = $request->get('code');
            $findPaymentMethod->payment_method_id = $request->get('payment_method_id');
            $findPaymentMethod->description = $request->get('description');

            if ($findPaymentMethod->save()) {
                return $this->successResponse("Berhasil Mengedit Payment Method", 200, [
                    'data' => $findPaymentMethod
                ]);
            }

        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode(), []);
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
        }
    }
}

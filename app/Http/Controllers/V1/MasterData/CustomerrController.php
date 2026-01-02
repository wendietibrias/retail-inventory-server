<?php

namespace App\Http\Controllers\V1\MasterData;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Psr\Http\Client\NetworkExceptionInterface;

class CustomerrController extends Controller
{
     public function index(Request $request)
    {
        $request->validate([
            'page' => 'required|integer',
            'per_page' => 'required|integer',
            'is_public' => 'sometimes|boolean',
        ]);

        try {
            $perPage = $request->get('per_page');
            $isPublic = $request->get('is_public');
            $search = $request->get('search');

            if (!\App\Helper\CheckPermissionHelper::checkItHasPermission(['permission'=>PermissionEnum::MELIHAT_CUSTOMER,'is_public'=>$isPublic])) {
                return $this->errorResponse("Tidak Memiliki Hak Akses Untuk Fitur Ini", 403, []);
            }

            $Customers = Customer::with([]);

            if ($search) {
                $Customers->where(function ($query) use ($search) {
                    $query->where('name', 'like', "$search%");
                });
            }

            return $this->successResponse("Berhasil Mendapatkan Data Customer", 200, [
                'items' => $Customers->paginate($perPage)
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
            'name' => 'sometimes|string',
            'address' => 'sometimes|string',
            'phone' => 'sometimes|string',
            'email' => 'sometimes|string',
            'npwp' => 'sometimes|string',
            'description' => 'sometimes|string'
        ]);

        try {

           if (!\App\Helper\CheckPermissionHelper::checkItHasPermission(['permission'=>PermissionEnum::MEMBUAT_CUSTOMER,'is_public'=>false])) {
                return $this->errorResponse("Tidak Memiliki Hak Akses Untuk Fitur Ini", 403, []);
            }

            $createCustomer = Customer::create($request->all());
            if ($createCustomer->save()) {
                return $this->successResponse("Berhasil Membuat Data Customer", 200, [
                    'data' => []
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
        $request->validate([
            'name' => 'sometimes|string',
            'address' => 'sometimes|string',
            'phone' => 'sometimes|string',
            'email' => 'sometimes|string',
            'npwp' => 'sometimes|string',
            'description' => 'sometimes|string'
        ]);

        try {

             if (!\App\Helper\CheckPermissionHelper::checkItHasPermission(['permission'=>PermissionEnum::MENGEDIT_CUSTOMER,'is_public'=>false])) {
                return $this->errorResponse("Tidak Memiliki Hak Akses Untuk Fitur Ini", 403, []);
            }

            $findCustomer = Customer::find($id);

            if (!$findCustomer) {
                return $this->errorResponse("Customer Tidak Ditemukan", 404, []);
            }

            $findCustomer->update($request->all());

            if ($findCustomer->save()) {
                return $this->successResponse("Berhasil Mengedit Data Customer", 200, [
                    'data' => []
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

    public function delete($id)
    {
        try {

         
             if (!\App\Helper\CheckPermissionHelper::checkItHasPermission(['permission'=>PermissionEnum::MENGHAPUS_CUSTOMER,'is_public'=>false])) {
                return $this->errorResponse("Tidak Memiliki Hak Akses Untuk Fitur Ini", 403, []);
            }

            $findCustomer = Customer::find($id);

            if (!$findCustomer) {
                return $this->errorResponse("Customer Tidak Ditemukan", 404, []);
            }


            if ($findCustomer->delete()) {
                return $this->successResponse("Berhasil Menghapus Data Customer", 200, [
                    'data' => []
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
}

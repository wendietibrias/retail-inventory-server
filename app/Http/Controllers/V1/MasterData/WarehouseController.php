<?php

namespace App\Http\Controllers\V1\MasterData;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use CheckPermissionHelper;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Psr\Http\Client\NetworkExceptionInterface;

class WarehouseController extends Controller
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

            if (!\App\Helper\CheckPermissionHelper::checkItHasPermission(['permission' => PermissionEnum::MELIHAT_WAREHOUSE, 'is_public' => $isPublic])) {
                return $this->errorResponse("Tidak Memiliki Hak Akses Untuk Fitur Ini", 403, []);
            }


            $warehouses = Warehouse::with([]);

            if ($search) {
                $warehouses->where(function ($query) use ($search) {
                    $query->where('name', 'like', "$search%");
                });
            }

            return $this->successResponse("Berhasil Mendapatkan Data Gudang", 200, [
                'items' => $warehouses->paginate($perPage)
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
            'description' => 'sometimes|string'
        ]);

        try {

            if (!\App\Helper\CheckPermissionHelper::checkItHasPermission(['permission' => PermissionEnum::MEMBUAT_WAREHOUSE, 'is_public' => false])) {
                return $this->errorResponse("Tidak Memiliki Hak Akses Untuk Fitur Ini", 403, []);
            }


            $createWarehouse = Warehouse::create($request->all());
            if ($createWarehouse->save()) {
                return $this->successResponse("Berhasil Membuat Data Gudang", 200, [
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
            'description' => 'sometimes|string'
        ]);

        try {

            if (!\App\Helper\CheckPermissionHelper::checkItHasPermission(['permission' => PermissionEnum::MENGEDIT_WAREHOUSE, 'is_public' => false])) {
                return $this->errorResponse("Tidak Memiliki Hak Akses Untuk Fitur Ini", 403, []);
            }


            $findWarehouse = Warehouse::find($id);

            if (!$findWarehouse) {
                return $this->errorResponse("Gudang Tidak Ditemukan", 404, []);
            }

            $findWarehouse->update($request->all());

            if ($findWarehouse->save()) {
                return $this->successResponse("Berhasil Mengedit Data Gudang", 200, [
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

            if (!\App\Helper\CheckPermissionHelper::checkItHasPermission(['permission' => PermissionEnum::MENGHAPUS_WAREHOUSE, 'is_public' => false])) {
                return $this->errorResponse("Tidak Memiliki Hak Akses Untuk Fitur Ini", 403, []);
            }


            $findWarehouse = Warehouse::find($id);

            if (!$findWarehouse) {
                return $this->errorResponse("Gudang Tidak Ditemukan", 404, []);
            }


            if ($findWarehouse->delete()) {
                return $this->successResponse("Berhasil Menghapus Data Gudang", 200, [
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

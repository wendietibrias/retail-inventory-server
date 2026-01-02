<?php

namespace App\Http\Controllers\V1\MasterData;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use CheckPermissionHelper;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Psr\Http\Client\NetworkExceptionInterface;

class ProductCategoryController extends Controller
{
     public function index(Request $request)
    {
        $request->validate([
            'page' => 'required|integer',
            'per_page' => 'required|integer',
            'is_public' => 'sometimes|string',
        ]);

        try {
            $perPage = $request->get('per_page');
            $isPublic = $request->get('is_public');
            $search = $request->get('search');

            if (!\App\Helper\CheckPermissionHelper::checkItHasPermission(['permission'=>PermissionEnum::MELIHAT_PRODUCT_CATEGORY, 'is_public'=>$isPublic])) {
                return $this->errorResponse("Tidak Memiliki Hak Akses Untuk Fitur Ini", 403, []);
            }

            $ProductCategorys = ProductCategory::where('deleted_at', null);

            if ($search) {
                $ProductCategorys->where(function ($query) use ($search) {
                    $query->where('name', 'like', "$search%");
                });
            }

            return $this->successResponse("Berhasil Mendapatkan Data ProductCategory", 200, [
                'items' => $ProductCategorys->paginate($perPage)
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
            'description' => 'sometimes|string'
        ]);

        try {

            if (!\App\Helper\CheckPermissionHelper::checkItHasPermission(['permission'=>PermissionEnum::MEMBUAT_PRODUCT_CATEGORY, 'is_public'=>false])) {
                return $this->errorResponse("Tidak Memiliki Hak Akses Untuk Fitur Ini", 403, []);
            }

            $createProductCategory = ProductCategory::create($request->all());
            if ($createProductCategory->save()) {
                return $this->successResponse("Berhasil Membuat Data ProductCategory", 200, [
                    'data' => []
                ]);
            }


        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(),500, []);

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
            'description' => 'sometimes|string'
        ]);

        try {

            if (!\App\Helper\CheckPermissionHelper::checkItHasPermission(['permission'=>PermissionEnum::MEMBUAT_PRODUCT_CATEGORY,'is_public'=>false])) {
                return $this->errorResponse("Tidak Memiliki Hak Akses Untuk Fitur Ini", 403, []);
            }

            $findProductCategory = ProductCategory::find($id);

            if (!$findProductCategory) {
                return $this->errorResponse("ProductCategory Tidak Ditemukan", 404, []);
            }

            $findProductCategory->update($request->all());

            if ($findProductCategory->save()) {
                return $this->successResponse("Berhasil Mengedit Data ProductCategory", 200, [
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

            if (!\App\Helper\CheckPermissionHelper::checkItHasPermission(['permission'=>PermissionEnum::MEMBUAT_PRODUCT_CATEGORY,'is_public'=>false])) {
                return $this->errorResponse("Tidak Memiliki Hak Akses Untuk Fitur Ini", 403, []);
            }

            $findProductCategory = ProductCategory::find($id);

            if (!$findProductCategory) {
                return $this->errorResponse("ProductCategory Tidak Ditemukan", 404, []);
            }


            if ($findProductCategory->delete()) {
                return $this->successResponse("Berhasil Menghapus Data ProductCategory", 200, [
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

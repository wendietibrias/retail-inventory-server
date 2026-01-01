<?php

namespace App\Http\Controllers\V1\MasterData;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Models\Product;
use CheckPermissionHelper;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Psr\Http\Client\NetworkExceptionInterface;

class ProductController extends Controller
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

            if (!\App\Helper\CheckPermissionHelper::checkItHasPermission(['permission'=>PermissionEnum::MELIHAT_PRODUCT, 'is_public'=>$isPublic])) {
                return $this->errorResponse("Tidak Memiliki Hak Akses Untuk Fitur Ini", 403, []);
            }

            $Products = Product::with([]);

            if ($search) {
                $Products->where(function ($query) use ($search) {
                    $query->where('name', 'like', "$search%");
                });
            }

            return $this->successResponse("Berhasil Mendapatkan Data Product", 200, [
                'items' => $Products->paginate($perPage)
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
            'description' => 'sometimes|string',
            'product_category_id' => 'required|integer',
        ]);

        try {

             if (!\App\Helper\CheckPermissionHelper::checkItHasPermission(['permission'=>PermissionEnum::MEMBUAT_PRODUCT, 'is_public'=>false])) {
                return $this->errorResponse("Tidak Memiliki Hak Akses Untuk Fitur Ini", 403, []);
            }

            $createProduct = Product::create($request->all());
            if ($createProduct->save()) {
                return $this->successResponse("Berhasil Membuat Data Product", 200, [
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
            'description' => 'sometimes|string',
            'product_category_id' => 'required|integer',
        ]);

        try {

           if (!\App\Helper\CheckPermissionHelper::checkItHasPermission(['permission'=>PermissionEnum::MENGEDIT_PRODUCT, 'is_public'=>false])) {
                return $this->errorResponse("Tidak Memiliki Hak Akses Untuk Fitur Ini", 403, []);
            }


            $findProduct = Product::find($id);

            if (!$findProduct) {
                return $this->errorResponse("Product Tidak Ditemukan", 404, []);
            }

            $findProduct->update($request->all());

            if ($findProduct->save()) {
                return $this->successResponse("Berhasil Mengedit Data Product", 200, [
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

             if (!\App\Helper\CheckPermissionHelper::checkItHasPermission(['permission'=>PermissionEnum::MENGHAPUS_PRODUCT, 'is_public'=>false])) {
                return $this->errorResponse("Tidak Memiliki Hak Akses Untuk Fitur Ini", 403, []);
            }


            $findProduct = Product::find($id);

            if (!$findProduct) {
                return $this->errorResponse("Product Tidak Ditemukan", 404, []);
            }


            if ($findProduct->delete()) {
                return $this->successResponse("Berhasil Menghapus Data Product", 200, [
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

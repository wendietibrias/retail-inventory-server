<?php

namespace App\Http\Controllers\V1\MasterData;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Models\ProductSku;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Psr\Http\Client\NetworkExceptionInterface;
use Storage;
use Str;

class ProductSkuSkuController extends Controller
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

            if (!\App\Helper\CheckPermissionHelper::checkItHasPermission(['permission' => PermissionEnum::MELIHAT_PRODUCT_SKU, 'is_public' => $isPublic])) {
                return $this->errorResponse("Tidak Memiliki Hak Akses Untuk Fitur Ini", 403, []);
            }

            $ProductSkus = ProductSku::with([]);

            if ($search) {
                $ProductSkus->where(function ($query) use ($search) {
                    $query->where('name', 'like', "$search%");
                });
            }

            return $this->successResponse("Berhasil Mendapatkan Data ProductSku", 200, [
                'items' => $ProductSkus->paginate($perPage)
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
            'sku_number' => 'required|string',
            'unit' => 'required|string',
            'size' => 'sometimes|string',
            'color' => 'sometimes|string',
            'name' => 'required|string',
            'product_id' => 'required|integer',
            'file' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        try {

            if (!\App\Helper\CheckPermissionHelper::checkItHasPermission(['permission' => PermissionEnum::MEMBUAT_PRODUCT_SKU, 'is_public' => false])) {
                return $this->errorResponse("Tidak Memiliki Hak Akses Untuk Fitur Ini", 403, []);
            }

            $createProductSku = ProductSku::create($request->all());
            if ($request->hasFile('file')) {
                $extension = $request->file('file')->getClientOriginalExtension();
                $fileName = "$createProductSku->skuNumber" . "-" . Str::random(5) . "-" . time() . "." . "$extension";
                $filePath = $request->file('file')->storeAs('assets/photos', $fileName, 'public');

                $createProductSku->photo_name = $fileName;
                $createProductSku->photo_url = $filePath;
            }

            if ($createProductSku->save()) {
                return $this->successResponse("Berhasil Membuat Data ProductSku", 200, [
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
            'sku_number' => 'required|string',
            'unit' => 'required|string',
            'size' => 'sometimes|string',
            'color' => 'sometimes|string',
            'name' => 'required|string',
            'product_id' => 'required|integer'
        ]);

        try {

            if (!\App\Helper\CheckPermissionHelper::checkItHasPermission(['permission' => PermissionEnum::MENGEDIT_PRODUCT_SKU, 'is_public' => false])) {
                return $this->errorResponse("Tidak Memiliki Hak Akses Untuk Fitur Ini", 403, []);
            }


            $findProductSku = ProductSku::find($id);

            if (!$findProductSku) {
                return $this->errorResponse("ProductSku Tidak Ditemukan", 404, []);
            }

            if ($request->hasFile('file')) {
                if ($findProductSku->photo_url && Storage::disk('public')->exists($findProductSku->photo_url)) {
                    Storage::disk('public')->delete($findProductSku->photo_url);
                }
                $extension = $request->file('file')->getClientOriginalExtension();
                $fileName = "$findProductSku->skuNumber" . "-" . Str::random(5) . "-" . time() . "." . "$extension";
                $filePath = $request->file('file')->storeAs('assets/photos', $fileName, 'public');

                $findProductSku->photo_name = $fileName;
                $findProductSku->photo_url = $filePath;
            }

            $findProductSku->update($request->all());

            if ($findProductSku->save()) {
                return $this->successResponse("Berhasil Mengedit Data ProductSku", 200, [
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

            if (!\App\Helper\CheckPermissionHelper::checkItHasPermission(['permission'=>PermissionEnum::MENGHAPUS_PRODUCT_SKU, 'is_public'=>false])) {
                return $this->errorResponse("Tidak Memiliki Hak Akses Untuk Fitur Ini", 403, []);
            }

            $findProductSku = ProductSku::find($id);

            if (!$findProductSku) {
                return $this->errorResponse("ProductSku Tidak Ditemukan", 404, []);
            }


            if ($findProductSku->delete()) {
                return $this->successResponse("Berhasil Menghapus Data ProductSku", 200, [
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

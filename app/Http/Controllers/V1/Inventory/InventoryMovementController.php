<?php

namespace App\Http\Controllers\V1\Inventory;

use App\Enums\PermissionEnum;
use App\Helper\CheckPermissionHelper;
use App\Http\Controllers\Controller;
use App\Models\InventoryMovement;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Psr\Http\Client\NetworkExceptionInterface;

class InventoryMovementController extends Controller
{
     public function index(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'sometimes|integer',
            'product_sku_id' => 'sometimes|integer',
            'type'=>'sometimes|string',
            'origin'=>'sometimes|string',
            'page' => 'required|integer',
            'per_page' => 'required|integer'
        ]);

        try {
            $perPage = $request->get('per_page');
            $isPublic = $request->get('is_public');
            $inventories = InventoryMovement::with(['productSku', 'warehouse'])->where('deleted_at', null);

            if (!CheckPermissionHelper::checkItHasPermission(['permission' => PermissionEnum::MELIHAT_INVENTORY_MOVEMENT, 'is_public' => $isPublic])) {
                return $this->errorResponse("Tidak Memiliki Hak Akses Untuk Fitur Ini", 403, []);
            }

            if($request->has('warehouse_id')){
                $warehouseId = $request->get('warehouse_id');
                $inventories->where('warehouse_id', $warehouseId);
            }

            if($request->has('origin')){
                $origin = $request->get('origin');
                $inventories->where('origin', $origin);
            }

            if($request->has('type')){
                $type = $request->get('type');
                $inventories->where('type', $type);
            }

            if($request->has('product_sku_id')){
                $productSkuId = $request->get('product_sku_id');
                $inventories->where('product_sku_id', $productSkuId);
            }

            return $this->successResponse("Berhasil Mendapatkan Data Inventory Movement", 200, ['items'=>$inventories->paginate($perPage)]);

        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500, []);

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

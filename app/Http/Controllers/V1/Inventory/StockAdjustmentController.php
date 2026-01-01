<?php

namespace App\Http\Controllers\V1\Inventory;

use App\Enums\PermissionEnum;
use App\Helper\CheckPermissionHelper;
use App\Helper\updateInventoryHelper;
use App\Http\Controllers\Controller;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentDetail;
use DB;
use FFI\Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Psr\Http\Client\NetworkExceptionInterface;

class StockAdjustmentController extends Controller
{
            public function index(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'sometimes|integer',
            'product_sku_id' => 'sometimes|integer',
            'type' => 'sometimes|string',
            'origin' => 'sometimes|string',
            'page' => 'required|integer',
            'per_page' => 'required|integer'
        ]);

        try {
            $perPage = $request->get('per_page');
            $isPublic = $request->get('is_public');
            $inventories =StockAdjustment::with(['stockAdjustmentDetails'=> function($query){
                $query->with(['productSku']);
            },'warehouse'])->where('deleted_at', null);

            if (!CheckPermissionHelper::checkItHasPermission(['permission' => PermissionEnum::MELIHAT_PENYESUAIAN_STOK, 'is_public' => $isPublic])) {
                return $this->errorResponse("Tidak Memiliki Hak Akses Untuk Fitur Ini", 403, []);
            }

            if ($request->has('warehouse_id')) {
                $warehouseId = $request->get('warehouse_id');
                $inventories->where('warehouse_id', $warehouseId);
            }

            if ($request->has('origin')) {
                $origin = $request->get('origin');
                $inventories->where('origin', $origin);
            }

            if ($request->has('type')) {
                $type = $request->get('type');
                $inventories->where('type', $type);
            }

            if ($request->has('product_sku_id')) {
                $productSkuId = $request->get('product_sku_id');
                $inventories->where('product_sku_id', $productSkuId);
            }

            return $this->successResponse("Berhasil Mendapatkan Data Inventory Movement", 200, ['items' => $inventories->paginate($perPage)]);

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

    public function create(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'description' => 'sometimes|string',
            'to_warehouse_id' => 'required|integer',
            'from_warehouse_id' => 'required|integer',
            'stock_adjustment_details' => 'required|array'
        ]);

        try {
            DB::beginTransaction();
            $user = auth()->user();

            $createStockAdjustmentStockAdjustment =StockAdjustment::create($request->except('stock_adjustment_details'));

            $createStockAdjustmentStockAdjustment->created_by_id = $user->id;

            $createStockAdjustmentStockAdjustment->save();

            $StockAdjustmentStockAdjustment = $createStockAdjustmentStockAdjustment->fresh();

            $payloadStockAdjustmentStockAdjustmentDetails = [];

            foreach ($request->get('stock_adjustment_details') as $mutationDetail) {
                $payloadStockAdjustmentStockAdjustmentDetails[] = array_merge($mutationDetail, [
                    'stock_adjustment_id' => $StockAdjustmentStockAdjustment->id
                ]);
            }

           StockAdjustmentDetail::insert($payloadStockAdjustmentStockAdjustmentDetails);

            DB::commit();

            return $this->successResponse("Berhasil Membuat Data Mutasi Masuk", 200, [
                'data' => $createStockAdjustmentStockAdjustment->fresh()
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage(), 500, []);

        } catch (QueryException $qeq) {
            DB::rollBack();

            if ($qeq->getCode() === '23000' || str_contains($qeq->getMessage(), 'Integrity constraint violation')) {
                return $this->errorResponse('error', 'Gagal menghapus! Data ini masih memiliki relasi aktif di tabel lain. Harap hapus relasi terkait terlebih dahulu.');
            }
            return $this->errorResponse("Internal Server Error", 500, []);
        } catch (NetworkExceptionInterface $nei) {
            DB::rollBack();

            return $this->errorResponse($nei->getMessage(), 500, []);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'date' => 'required|date',
            'description' => 'sometimes|string',
            'to_warehouse_id' => 'required|integer',
            'from_warehouse_id' => 'required|integer',
            'stock_adjustment_details' => 'required|array'
        ]);
        try {
            DB::beginTransaction();

            $findStockAdjustmentStockAdjustment =StockAdjustment::with(['stockAdjustmentDetails'])->where('deleted_at', null)->where('id', $id)->first();
            if (!$findStockAdjustmentStockAdjustment) {
                return $this->errorResponse("Mutasi Masuk Tidak Ditemukan", 404, []);
            }

            $StockAdjustmentStockAdjustmentDetails = collect($findStockAdjustmentStockAdjustment->PENYESUAIMELIHAT_PENYESUAIAN_STOKMELIHAT_PENYESUAIAN_STOK_details)->toArray();
            $requestedMutationDetails = $request->get('PENYESUAIMELIHAT_PENYESUAIAN_STOKMELIHAT_PENYESUAIAN_STOK_details');

            $findStockAdjustmentStockAdjustment->update($request->except('stock_adjustment_details'));

            $deletedArrayItem = [];

            if (count($request->get('stock_adjustment_details')) >= count($StockAdjustmentStockAdjustmentDetails)) {
                foreach ($request->get('stock_adjustment_details') as $StockAdjustmentStockAdjustmentDetailItem) {
                    $key = array_column($StockAdjustmentStockAdjustmentDetails, 'id');
                    $findedKey = array_search($StockAdjustmentStockAdjustmentDetailItem->id, $key, false);
                    if (gettype($findedKey) === 'integer') {
                        $StockAdjustmentStockAdjustmentDetails[$findedKey] = $StockAdjustmentStockAdjustmentDetailItem;
                    } else {
                        $StockAdjustmentStockAdjustmentDetails[] = array_merge($StockAdjustmentStockAdjustmentDetailItem, [
                            'stock_adjustment_id' => $findStockAdjustmentStockAdjustment->id,
                        ]);
                    }
                }
            } else {
                foreach ($StockAdjustmentStockAdjustmentDetails as $detailKey => $mutationDetailItem) {
                    $key = array_column($requestedMutationDetails, 'id');
                    $findedKey = array_search($mutationDetailItem->id, $key, false);
                    if (gettype($findedKey) !== 'integer') {
                        $deletedArrayItem[] = $StockAdjustmentStockAdjustmentDetails['id'];
                        unset($StockAdjustmentStockAdjustmentDetails[$detailKey]);
                    }
                }
            }

            StockAdjustment::updateOrCreate($StockAdjustmentStockAdjustmentDetails);
            StockAdjustmentDetail::whereIn('id', $deletedArrayItem)->delete();

            DB::commit();

        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage(), 500, []);

        } catch (QueryException $qeq) {
            DB::rollBack();

            if ($qeq->getCode() === '23000' || str_contains($qeq->getMessage(), 'Integrity constraint violation')) {
                return $this->errorResponse('error', 'Gagal menghapus! Data ini masih memiliki relasi aktif di tabel lain. Harap hapus relasi terkait terlebih dahulu.');
            }
            return $this->errorResponse("Internal Server Error", 500, []);
        } catch (NetworkExceptionInterface $nei) {
            DB::rollBack();

            return $this->errorResponse($nei->getMessage(), 500, []);
        }
    }

    public function changeStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string'
        ]);

        try {
            DB::beginTransaction();

            $user = auth()->user();

            $findStockAdjustmentStockAdjustment = StockAdjustment::with(['stockAdjustmentDetails'])->where('deleted_at', null)->where('id', $id)->first();
            if (!$findStockAdjustmentStockAdjustment) {
                return $this->errorResponse("Mutasi Masuk Tidak Ditemukan", 404, []);
            }

            $StockAdjustmentStockAdjustmentDetails = collect($findStockAdjustmentStockAdjustment->PENYESUAIMELIHAT_PENYESUAIAN_STOKMELIHAT_PENYESUAIAN_STOK_details)->toArray();

            if($request->get('status') === 'DISETUJUI'){
                $productSkuIds = [];
                $warehouseIds = [];

                foreach($StockAdjustmentStockAdjustmentDetails as $mutationDetailIn){
                    $productSkuIds[]=$mutationDetailIn['id'];
                    $warehouseIds[] = $findStockAdjustmentStockAdjustment->warehouse_id;
                }

                $findStockAdjustmentStockAdjustment->approve_by_id = $user->id;

                $updateInventory = updateInventoryHelper::updateInventory([
                    'origin'=>"ADJUSTMENT",
                    'type'=>'ADJUSTMENT',
                    'reference_code'=>$findStockAdjustmentStockAdjustment->code,
                    'warehouse_ids'=>$warehouseIds,
                    'product_sku_ids'=>$productSkuIds,
                    'details'=> $findStockAdjustmentStockAdjustment->PENYESUAIMELIHAT_PENYESUAIAN_STOKMELIHAT_PENYESUAIAN_STOK_details,
                ]);

                if($updateInventory && $updateInventory['status'] === 'error'){
                    DB::rollBack();
                    return $this->errorResponse($updateInventory['message'], 400, []);
                }
            } else {
                $findStockAdjustmentStockAdjustment->reject_by_id = $user->id;
            }


            DB::commit();

            return $this->successResponse("Berhasil Mengubah Status Mutasi Masuk", 200, []);

        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage(), 500, []);

        } catch (QueryException $qeq) {
            DB::rollBack();

            if ($qeq->getCode() === '23000' || str_contains($qeq->getMessage(), 'Integrity constraint violation')) {
                return $this->errorResponse('error', 'Gagal menghapus! Data ini masih memiliki relasi aktif di tabel lain. Harap hapus relasi terkait terlebih dahulu.');
            }
            return $this->errorResponse("Internal Server Error", 500, []);
        } catch (NetworkExceptionInterface $nei) {
            DB::rollBack();

            return $this->errorResponse($nei->getMessage(), 500, []);
        }
    }
}

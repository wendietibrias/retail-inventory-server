<?php

namespace App\Http\Controllers\V1\Inventory;

use App\Enums\PermissionEnum;
use App\Helper\CheckPermissionHelper;
use App\Helper\updateInventoryHelper;
use App\Http\Controllers\Controller;
use App\Models\MutationIn;
use App\Models\MutationInDetail;
use DB;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Psr\Http\Client\NetworkExceptionInterface;

class MutationInController extends Controller
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
            $inventories = MutationIn::with(['mutationInDetails', 'toWarehouse', 'fromWarehouse'])->where('deleted_at', null);

            if (!CheckPermissionHelper::checkItHasPermission(['permission' => PermissionEnum::MELIHAT_MUTATION_IN, 'is_public' => $isPublic])) {
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
            'mutation_in_details' => 'required|array'
        ]);

        try {
            DB::beginTransaction();
            $user = auth()->user();

            $createMutationIn = MutationIn::create($request->except('mutation_in_details'));

            $createMutationIn->created_by_id = $user->id;

            $createMutationIn->save();

            $mutationIn = $createMutationIn->fresh();

            $payloadMutationInDetails = [];

            foreach ($request->get('mutation_in_details') as $mutationDetail) {
                $payloadMutationInDetails[] = array_merge($mutationDetail, [
                    'mutation_in_id' => $mutationIn->id
                ]);
            }

            MutationInDetail::insert($payloadMutationInDetails);

            DB::commit();

            return $this->successResponse("Berhasil Membuat Data Mutasi Masuk", 200, [
                'data' => $createMutationIn->fresh()
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
            'mutation_in_details' => 'required|array'
        ]);
        try {
            DB::beginTransaction();

            $findMutationIn = MutationIn::with(['mutationInDetails'])->where('deleted_at', null)->where('id', $id)->first();
            if (!$findMutationIn) {
                return $this->errorResponse("Mutasi Masuk Tidak Ditemukan", 404, []);
            }

            $mutationInDetails = collect($findMutationIn->mutation_in_details)->toArray();
            $requestedMutationDetails = $request->get('mutation_in_details');

            $findMutationIn->update($request->except('mutation_in_details'));

            $deletedArrayItem = [];

            if (count($request->get('mutation_in_details')) >= count($mutationInDetails)) {
                foreach ($request->get('mutation_in_details') as $mutationInDetailItem) {
                    $key = array_column($mutationInDetails, 'id');
                    $findedKey = array_search($mutationInDetailItem->id, $key, false);
                    if (gettype($findedKey) === 'integer') {
                        $mutationInDetails[$findedKey] = $mutationInDetailItem;
                    } else {
                        $mutationInDetails[] = array_merge($mutationInDetailItem, [
                            'mutation_in_id' => $findMutationIn->id,
                        ]);
                    }
                }
            } else {
                foreach ($mutationInDetails as $detailKey => $mutationDetailItem) {
                    $key = array_column($requestedMutationDetails, 'id');
                    $findedKey = array_search($mutationDetailItem->id, $key, false);
                    if (gettype($findedKey) !== 'integer') {
                        $deletedArrayItem[] = $mutationInDetails['id'];
                        unset($mutationInDetails[$detailKey]);
                    }
                }
            }

            MutationInDetail::updateOrCreate($mutationInDetails);
            MutationInDetail::whereIn('id', $deletedArrayItem)->delete();

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

            $findMutationIn = MutationIn::with(['mutationInDetails'])->where('deleted_at', null)->where('id', $id)->first();
            if (!$findMutationIn) {
                return $this->errorResponse("Mutasi Masuk Tidak Ditemukan", 404, []);
            }

            $mutationInDetails = collect($findMutationIn->mutation_in_details)->toArray();

            if($request->get('status') === 'DISETUJUI'){
                $productSkuIds = [];
                $warehouseIds = [];

                foreach($mutationInDetails as $mutationDetailIn){
                    $productSkuIds[]=$mutationDetailIn['id'];
                    $warehouseIds[] = $findMutationIn->warehouse_id;
                }

                $findMutationIn->approve_by_id = $user->id;

                $updateInventory = updateInventoryHelper::updateInventory([
                    'origin'=>"MUTATION IN",
                    'type'=>'IN',
                    'reference_code'=>$findMutationIn->code,
                    'warehouse_ids'=>$warehouseIds,
                    'product_sku_ids'=>$productSkuIds,
                    'details'=> $findMutationIn->mutation_in_details,
                ]);

                if($updateInventory && $updateInventory['status'] === 'error'){
                    DB::rollBack();
                    return $this->errorResponse($updateInventory['message'], 400, []);
                }
            } else {
                $findMutationIn->reject_by_id = $user->id;
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

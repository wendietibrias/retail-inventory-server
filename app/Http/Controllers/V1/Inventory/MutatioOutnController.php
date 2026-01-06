<?php

namespace App\Http\Controllers\V1\Inventory;

use App\Enums\MutationInStatusEnum;
use App\Enums\PermissionEnum;
use App\Helper\CheckPermissionHelper;
use App\Helper\updateInventoryHelper;
use App\Http\Controllers\Controller;
use App\Models\MutationIn;
use App\Models\MutationInDetail;
use App\Models\MutationOut;
use App\Models\MutationOutDetail;
use Carbon\Carbon;
use DB;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Psr\Http\Client\NetworkExceptionInterface;

class MutatioOutnController extends Controller
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
            $inventories = MutationOut::with(['MutationOutDetails', 'toWarehouse', 'fromWarehouse'])->where('deleted_at', null);

            if (!CheckPermissionHelper::checkItHasPermission(['permission' => PermissionEnum::MELIHAT_MUTATION_OUT, 'is_public' => $isPublic])) {
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
            'mutation_out_details' => 'required|array'
        ]);

        try {
            DB::beginTransaction();
            $user = auth()->user();


            $now = Carbon::now();
            $year = $now->year;
            $month = $now->month;
            $latestMutationOut = MutationOut::where('deleted_at', null)->latest()->first();

            $count = $latestMutationOut ? $latestMutationOut->id + 1 : 1;

            $code = "MUTATIONOUT/$year$month/$count";

            $createMutationOut = MutationOut::create(array_merge(
                $request->except('mutation_out_details'),
                ['code' => $code, 'created_by_id' => $user->id]
            ));

            $createMutationOut->created_by_id = $user->id;


            $createMutationOut->save();

            $MutationOut = $createMutationOut->fresh();

            $payloadMutationOutDetails = [];

            foreach ($request->get('mutation_out_details') as $mutationDetail) {
                $payloadMutationOutDetails[] = array_merge($mutationDetail, [
                    'MUTATION_OUTMELIHAT_MUTATION_OUT_id' => $MutationOut->id
                ]);
            }

            MutationOutDetail::insert($payloadMutationOutDetails);

            DB::commit();

            return $this->successResponse("Berhasil Membuat Data Mutasi Masuk", 200, [
                'data' => $createMutationOut->fresh()
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
            'mutation_out_details' => 'required|array'
        ]);
        try {
            DB::beginTransaction();

            $findMutationOut = MutationOut::with(['MutationOutDetails'])->where('deleted_at', null)->where('id', $id)->first();
            if (!$findMutationOut) {
                return $this->errorResponse("Mutasi Masuk Tidak Ditemukan", 404, []);
            }

            $MutationOutDetails = collect($findMutationOut->mutation_out_details)->toArray();
            $requestedMutationDetails = $request->get('mutation_out_details');

            $findMutationOut->update($request->except('mutation_out_details'));

            $deletedArrayItem = [];

            if (count($request->get('mutation_out_details')) >= count($MutationOutDetails)) {
                foreach ($request->get('mutation_out_details') as $MutationOutDetailItem) {
                    $key = array_column($MutationOutDetails, 'id');
                    $findedKey = array_search($MutationOutDetailItem->id, $key, false);
                    if (gettype($findedKey) === 'integer') {
                        $MutationOutDetails[$findedKey] = $MutationOutDetailItem;
                    } else {
                        $MutationOutDetails[] = array_merge($MutationOutDetailItem, [
                            'mutation_out_id' => $findMutationOut->id,
                        ]);
                    }
                }
            } else {
                foreach ($MutationOutDetails as $detailKey => $mutationDetailItem) {
                    $key = array_column($requestedMutationDetails, 'id');
                    $findedKey = array_search($mutationDetailItem->id, $key, false);
                    if (gettype($findedKey) !== 'integer') {
                        $deletedArrayItem[] = $MutationOutDetails['id'];
                        unset($MutationOutDetails[$detailKey]);
                    }
                }
            }

            MutationOutDetail::updateOrCreate($MutationOutDetails);
            MutationOutDetail::whereIn('id', $deletedArrayItem)->delete();

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

            $findMutationOut = MutationOut::with(['MutationOutDetails'])->where('deleted_at', null)->where('id', $id)->first();
            if (!$findMutationOut) {
                return $this->errorResponse("Mutasi Masuk Tidak Ditemukan", 404, []);
            }

            $MutationOutDetails = collect($findMutationOut->mutation_out_details)->toArray();

            if ($request->get('status') === 'DISETUJUI') {
                $productSkuIds = [];
                $warehouseIds = [];
                $warehouseIds[] = $findMutationOut->warehouse_id;

                foreach ($MutationOutDetails as $mutationDetailIn) {
                    $productSkuIds[] = $mutationDetailIn['product_sku_id'];
                }

                $findMutationOut->approve_by_id = $user->id;

                $updateInventory = updateInventoryHelper::updateInventory([
                    'origin' => "MUTATION OUT",
                    'type' => 'OUT',
                    'reference_code' => $findMutationOut->code,
                    'warehouse_ids' => $warehouseIds,
                    'product_sku_ids' => $productSkuIds,
                    'details' => $findMutationOut->mutation_out_details,
                ]);

                $now = Carbon::now();
                $year = $now->year;
                $month = $now->month;
                $latestMutationOut = MutationIn::where('deleted_at', null)->latest()->first();

                $count = $latestMutationOut ? $latestMutationOut->id + 1 : 1;

                $code = "MUTATIONIN/$year$month/$count";


                $createMutationIn = MutationIn::create([
                    'code' => $code,
                    'mutation_out_id' => $findMutationOut->id,
                    'from_warehouse_id' => $findMutationOut->from_warehouse_id,
                    'to_warehouse_id' => $findMutationOut->to_warehouse_id,
                    'date' => $findMutationOut->date,
                    'status' => MutationInStatusEnum::DIBUAT,
                ]);

                $createMutationIn->save();

                $savedMutationIn = $createMutationIn->fresh();

                $payloadMutationIn = [];

                $mutationOutDetails = collect($findMutationOut->mutationOutDetails)->toArray();

                foreach ($mutationOutDetails as $mutationOutDetail) {
                    $payloadMutationIn[] = [
                        'product_sku_id' => $mutationOutDetail['product_sku_id'],
                        'mutation_in_id' => $savedMutationIn->id,
                        'qty' => $mutationOutDetail['qty'],
                    ];
                }

                MutationInDetail::insert($payloadMutationIn);

                if ($updateInventory && $updateInventory['status'] === 'error') {
                    DB::rollBack();
                    return $this->errorResponse($updateInventory['message'], 400, []);
                }
            } else {
                $findMutationOut->reject_by_id = $user->id;
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

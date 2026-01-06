<?php

namespace App\Http\Controllers\V1\Transaction;

use App\Enums\PermissionEnum;
use App\Helper\updateInventoryHelper;
use App\Http\Controllers\Controller;
use App\Models\Outbound;
use App\Models\OutboundDetail;
use Carbon\Carbon;
use DB;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Psr\Http\Client\NetworkExceptionInterface;

class OutboundController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'page' => 'required|integer',
            'per_page' => 'required|integer',
            'is_public' => 'sometimes|boolean',
            'search' => 'sometimes|string'
        ]);

        try {
            $perPage = $request->get('per_page');
            $isPublic = $request->get('is_public');
            $search = $request->get('search');

            if (!\App\Helper\CheckPermissionHelper::checkItHasPermission(['permission' => PermissionEnum::MENYETUJUI_OUTBOUND, 'is_public' => $isPublic])) {
                return $this->errorResponse("Tidak Memiliki Hak Akses Untuk Fitur Ini", 403, []);
            }

            $Products = Outbound::with([
                'customer',
                'createdBy',
                'warehouse',
                'outboundDetails' => function ($query) {
                    return $query->with(['productSku']);
                }
            ]);


            if ($search) {
                $Products->where(function ($query) use ($search) {
                    $query->where('code', 'like', "$search%")->orWhereHas('customer', function ($query) use ($search) {
                        return $query->where('name', 'like', "$search%");
                    });
                });
            }

            return $this->successResponse("Berhasil Mendapatkan Data Product", 200, [
                'items' => $Products->paginate($perPage)
            ]);

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

    public function detail($id)
    {
        try {
            $findOutbound = Outbound::with([
                'warehouse',
                'supplier',
                'OutboundDetails' => function ($query) {
                    return $query->with(['productSku']);
                }
            ])->where('deleted_at', null)->where('id', $id)->first();

            if (!$findOutbound) {
                return $this->errorResponse("Outbound Tidak Ditemukan", 404, []);
            }

            return $this->successResponse("Berhasil Mendapatkan Data Outbound", 200, []);

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
            'warehouse_id' => 'required|integer',
            'customer_id' => 'required|integer',
            'out_bound_details' => 'required|array'
        ]);
        try {
            DB::beginTransaction();
            $user = auth()->user();

            if (
                !\App\Helper\CheckPermissionHelper::checkItHasPermission([
                    'permission' => PermissionEnum::MEMBUAT_OUTBOUND
                    ,
                    'is_public' => false
                ])
            ) {
                return $this->errorResponse("Tidak Memiliki Hak Akses Untuk Fitur Ini", 403, []);
            }

            $latestOutbound = Outbound::where('deleted_at', null)->latest()->first();

            $now = Carbon::now();
            $year = $now->year;
            $month = $now->month;

            $count = $latestOutbound ? $latestOutbound->id + 1 : 1;

            $code = "OUTBOUND/$year$month/$count";

            $createOutbound = Outbound::create(array_merge($request->except('out_bound_details'), [
                'created_by_id' => $user->id,
                'code' => $code
            ]));

            $createOutbound->created_by_id = $user->id;

            $payloadOutboundDetails = [];
            $createOutbound->save();

            $savedOutbound = $createOutbound->fresh();

            if ($savedOutbound) {

                foreach ($request->get('out_bound_details') as $OutboundDetailItem) {
                    $payloadOutboundDetails[] = array_merge($OutboundDetailItem, [
                        'outbound_id' => $savedOutbound->id,
                    ]);
                }
            }


            $grandTotal = 0;

            foreach ($payloadOutboundDetails as $inboundDetail) {
                $grandTotal += intval($inboundDetail['sub_total']);
            }

            $createOutbound->grand_total = $grandTotal;

            OutboundDetail::insert($payloadOutboundDetails);

            $createOutbound->save();

            DB::commit();

            return $this->successResponse("Berhasil Menambahkan Data Outbound", 200, [
                'data' => $createOutbound
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
            'warehouse_id' => 'required|integer',
            'customer_id' => 'required|integer',
            'out_bound_details' => 'required|array'
        ]);
        try {

            DB::beginTransaction();

            $user = auth()->user();

            if (
                !\App\Helper\CheckPermissionHelper::checkItHasPermission([
                    'permission' => PermissionEnum::MENGEDIT_OUTBOUND
                    ,
                    'is_public' => false
                ])
            ) {
                return $this->errorResponse("Tidak Memiliki Hak Akses Untuk Fitur Ini", 403, []);
            }

            $findOutbound = Outbound::where('deleted_at', null)->where('id', $id)->first();
            if (!$findOutbound) {
                return $this->errorResponse("Outbound Tidak Ditemukan", 404, []);
            }

            $findOutbound->update(array_merge($request->except('out_bound_details'), [
                'updated_by_id' => $user->id
            ]));

            /** update the details */

            $currentOutboundDetails = collect($findOutbound->OutboundDetails)->toArray();

            foreach ($request->get('out_bound_details') as $OutboundDetailItem) {
                $key = array_column($currentOutboundDetails, "id", null);
                $findedKey = array_search("id", $key, false);
                if (gettype($findedKey) === 'integer') {
                    $currentOutboundDetails[$findedKey] = $OutboundDetailItem;
                } else {
                    $currentOutboundDetails[] = $OutboundDetailItem;
                }
            }


            $grandTotal = 0;

            foreach ($currentOutboundDetails as $inboundDetail) {
                $grandTotal += intval($inboundDetail['sub_total']);
            }

            $findOutbound->grand_total = $grandTotal;


            OutboundDetail::updateOrCreate($currentOutboundDetails);

            $findOutbound->save();

            DB::commit();

            return $this->successResponse("Berhasil Menambahkan Data Outbound", 200, [
                'data' => $findOutbound
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

    public function changeStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string'
        ]);

        try {
            DB::beginTransaction();

            $user = auth()->user();

            $findOutbound = Outbound::with(['OutboundDetails'])->where('deleted_at', null)->where('id', $id)->first();

            if (!$findOutbound) {
                return $this->errorResponse("Outbound Tidak Ditemukan", 404, []);
            }

            $findOutbound->status = $request->get('status');

            if ($request->get('status') === 'DISETUJUI') {
                if (
                    !\App\Helper\CheckPermissionHelper::checkItHasPermission([
                        'permission' => PermissionEnum::MENYETUJUI_OUTBOUND
                        ,
                        'is_public' => false
                    ])
                ) {
                    return $this->errorResponse("Tidak Memiliki Hak Akses Untuk Fitur Ini", 403, []);
                }
                $findOutbound->approve_by_id = $user->id;

                $warehouseIds = [$findOutbound->warehouse_id];
                $productSkuIds = [];

                $details = collect($findOutbound->outboundDetails)->toArray();

                foreach ($details as $detail) {
                    $productSkuIds[] = $detail['product_sku_id'];
                }

                $updateInventory = updateInventoryHelper::updateInventory([
                    'reference_code' => $findOutbound->code,
                    'origin' => 'OUTBOUND',
                    'warehouse_ids' => $warehouseIds,
                    'product_sku_ids' => $productSkuIds,
                    'type' => 'OUT',
                    'details' => $findOutbound->OutboundDetails
                ]);

                if ($updateInventory && $updateInventory['status'] === 'error') {
                    DB::rollBack();
                    return $this->errorResponse($updateInventory['message'], 400, []);
                }
            }

            if ($request->get('status') === 'DITOLAK') {
                if (
                    !\App\Helper\CheckPermissionHelper::checkItHasPermission([
                        'permission' => PermissionEnum::MENOLAK_OUTBOUND
                        ,
                        'is_public' => false
                    ])
                ) {
                    return $this->errorResponse("Tidak Memiliki Hak Akses Untuk Fitur Ini", 403, []);
                }
                $findOutbound->reject_by_id = $user->id;
            }

            $findOutbound->save();

            DB::commit();

            return $this->successResponse("Berhasil Mengubah Status Outbound ", 200, []);

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

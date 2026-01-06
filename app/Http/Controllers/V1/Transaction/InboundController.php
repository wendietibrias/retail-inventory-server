<?php

namespace App\Http\Controllers\V1\Transaction;

use App\Enums\PermissionEnum;
use App\Helper\updateInventoryHelper;
use App\Http\Controllers\Controller;
use App\Models\Inbound;
use App\Models\InboundDetail;
use Carbon\Carbon;
use CheckPermissionHelper;
use DB;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Log;
use Psr\Http\Client\NetworkExceptionInterface;
use Symfony\Component\Console\Input\InputOption;

class InboundController extends Controller
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

            if (!\App\Helper\CheckPermissionHelper::checkItHasPermission(['permission' => PermissionEnum::MENYETUJUI_INBOUND, 'is_public' => $isPublic])) {
                return $this->errorResponse("Tidak Memiliki Hak Akses Untuk Fitur Ini", 403, []);
            }

            $Products = Inbound::with([
                'supplier',
                'warehouse',
                'createdBy',
                'inboundDetails' => function ($query) {
                    return $query->with(['productSku']);
                }
            ]);

            if ($search) {
                $Products->where(function ($query) use ($search) {
                    $query->where('code', 'like', "$search%")->orWhereHas('supplier', function ($query) use ($search) {
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
            $findInbound = Inbound::with([
                'warehouse',
                'supplier',
                'inboundDetails' => function ($query) {
                    return $query->with(['productSku']);
                }
            ])->where('deleted_at', null)->where('id', $id)->first();

            if (!$findInbound) {
                return $this->errorResponse("Inbound Tidak Ditemukan", 404, []);
            }

            return $this->successResponse("Berhasil Mendapatkan Data Inbound", 200, []);

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
            'supplier_id' => 'required|integer',
            'warehouse_id' => 'required|integer',
            'grand_total' => 'required|integer',

            'inbound_details' => 'required|array'
        ]);
        try {
            DB::beginTransaction();
            $user = auth()->user();

            if (
                !\App\Helper\CheckPermissionHelper::checkItHasPermission([
                    'permission' => PermissionEnum::MEMBUAT_INBOUND
                    ,
                    'is_public' => false
                ])
            ) {
                return $this->errorResponse("Tidak Memiliki Hak Akses Untuk Fitur Ini", 403, []);
            }

            $latestInbound = Inbound::where('deleted_at', null)->latest()->first();


            $now = Carbon::now();

            $year = $now->year;
            $month = $now->month;

            $count = !$latestInbound ? 1 : $latestInbound->id + 1;

            $code = "INBOUND/$year$month/$count";

            $createInbound = Inbound::create(array_merge($request->except('inbound_details'), [
                'code' => $code,
                'created_by_id' => $user->id
            ]));


            $createInbound->created_by_id = $user->id;

            $payloadInboundDetails = [];
            $createInbound->save();

            $savedInbound = $createInbound->fresh();


            if ($savedInbound) {
                foreach ($request->get('inbound_details') as $inboundDetailItem) {
                    $payloadInboundDetails[] = array_merge($inboundDetailItem, [
                        'inbound_id' => $savedInbound->id,
                    ]);
                }
            }

            InboundDetail::insert($payloadInboundDetails);

            $grandTotal = 0;

            foreach ($payloadInboundDetails as $inboundDetail) {
                $grandTotal += intval($inboundDetail['sub_total']);
            }

            $createInbound->grand_total = $grandTotal;

            $createInbound->save();

            DB::commit();

            return $this->successResponse("Berhasil Menambahkan Data Inbound", 200, [
                'data' => $createInbound
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
            'supplier_id' => 'required|integer',
            'inbound_details' => 'required|array'
        ]);
        try {

            DB::beginTransaction();

            $user = auth()->user();

            if (
                !\App\Helper\CheckPermissionHelper::checkItHasPermission([
                    'permission' => PermissionEnum::MENGEDIT_INBOUND
                    ,
                    'is_public' => false
                ])
            ) {
                return $this->errorResponse("Tidak Memiliki Hak Akses Untuk Fitur Ini", 403, []);
            }

            $findInbound = Inbound::where('deleted_at', null)->where('id', $id)->first();
            if (!$findInbound) {
                return $this->errorResponse("Inbound Tidak Ditemukan", 404, []);
            }

            $findInbound->update(array_merge($request->except('inbound_details'), [
                'updated_by_id' => $user->id
            ]));

            /** update the details */

            $currentInboundDetails = collect($findInbound->inboundDetails)->toArray();



            foreach ($request->get('inbound_details') as $inboundDetailItem) {
                $key = array_column($currentInboundDetails, "id", null);
                $findedKey = array_search("id", $key, false);
                if (gettype($findedKey) === 'integer') {
                    $currentInboundDetails[$findedKey] = $inboundDetailItem;
                } else {
                    $currentInboundDetails[] = $inboundDetailItem;
                }
            }

            $grandTotal = 0;

            foreach ($currentInboundDetails as $inboundDetail) {
                $grandTotal += intval($inboundDetail['sub_total']);
            }

            $findInbound->grand_total = $grandTotal;

            InboundDetail::updateOrCreate($currentInboundDetails);

            DB::commit();

            return $this->successResponse("Berhasil Menambahkan Data Inbound", 200, [
                'data' => $findInbound
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

            $findInbound = Inbound::with(['inboundDetails'])->where('deleted_at', null)->where('id', $id)->first();

            if (!$findInbound) {
                return $this->errorResponse("Inbound Tidak Ditemukan", 404, []);
            }

            $findInbound->status = $request->get('status');

            if ($request->get('status') === 'DISETUJUI') {
                if (
                    !\App\Helper\CheckPermissionHelper::checkItHasPermission([
                        'permission' => PermissionEnum::MENYETUJUI_INBOUND
                        ,
                        'is_public' => false
                    ])
                ) {
                    return $this->errorResponse("Tidak Memiliki Hak Akses Untuk Fitur Ini", 403, []);
                }
                $findInbound->approve_by_id = $user->id;

                $warehouseIds = [$findInbound->warehouse_id];
                $productSkuIds = [];

                $details = collect($findInbound->inboundDetails)->toArray();

                foreach ($details as $detail) {
                    $productSkuIds[] = $detail['product_sku_id'];
                }

                if(count($productSkuIds) < 1){
                    return $this->errorResponse("Tidak Ada Produk",400,[]);
                }

                $updateInventory = updateInventoryHelper::updateInventory([
                    'reference_code' => $findInbound->code,
                    'origin' => 'INBOUND',
                    'warehouse_ids' => $warehouseIds,
                    'product_sku_ids' => $productSkuIds,
                    'type' => 'IN',
                    'details' => $findInbound->inboundDetails
                ]);

                if ($updateInventory && $updateInventory['status'] === 'error') {
                    DB::rollBack();
                    return $this->errorResponse($updateInventory['message'], 400, []);
                }
            }

            if ($request->get('status') === 'DITOLAK') {
                if (
                    !\App\Helper\CheckPermissionHelper::checkItHasPermission([
                        'permission' => PermissionEnum::MENOLAK_INBOUND
                        ,
                        'is_public' => false
                    ])
                ) {
                    return $this->errorResponse("Tidak Memiliki Hak Akses Untuk Fitur Ini", 403, []);
                }
                $findInbound->reject_by_id = $user->id;
            }

            $findInbound->save();

            DB::commit();

            return $this->successResponse("Berhasil Mengubah Status Inbound ", 200, []);

        } catch (Exception $e) {
            Log::info($e);
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

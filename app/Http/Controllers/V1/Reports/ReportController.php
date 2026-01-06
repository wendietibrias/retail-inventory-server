<?php

namespace App\Http\Controllers\V1\Reports;

use App\Http\Controllers\Controller;
use App\Models\Inbound;
use DB;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Psr\Http\Client\NetworkExceptionInterface;

class ReportController extends Controller
{
    public function inboundReport(Request $request)
    {
        $request->validate([
            'year' => 'sometimes|integer'
        ]);

        try {
            $inboundData = DB::table('inbounds')
                ->join("warehouses", "inbounds.warehouse_id", '=', 'warehouses.id')
                ->select(
                    DB::raw('SUM(grand_total) as wholeTotal'),
                    DB::raw('YEAR(inbounds.created_at) as year'),
                    DB::raw('MONTH(inbounds.created_at) as month'),
                    'warehouses.id as warehouse_id',
                    'warehouses.name as warehouse_name'
                )
                ->groupBy('year', 'month', 'warehouse_id', 'warehouse_name')
                ->get();

            return $this->successResponse("Berhasil Mendapatkan Laporan Inbound", 200, [
                'data' => $inboundData
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

    public function outboundReport(Request $request)
    {
        $request->validate([
            'year' => 'sometimes|integer'
        ]);

        try {
            $inboundData = DB::table('outbounds')
                ->join("warehouses", "outbounds.warehouse_id", '=', 'warehouses.id')
                ->select(
                    DB::raw('SUM(grand_total) as wholeTotal'),
                    DB::raw('YEAR(outbounds.created_at) as year'),
                    DB::raw('MONTH(outbounds.created_at) as month'),
                    'warehouses.id as warehouse_id',
                    'warehouses.name as warehouse_name'
                )
                ->groupBy('year', 'month', 'warehouse_id', 'warehouse_name')
                ->get();

            return $this->successResponse("Berhasil Mendapatkan Laporan Outbound", 200, [
                'data' => $inboundData
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

    public function stockAdjustmentReport(Request $request)
    {
        $request->validate([
            'year' => 'sometimes|integer'
        ]);

        try {
            $inboundData = $inboundData = DB::table('stock_adjustments')
                ->join('stock_adjustment_details', 'stock_adjustments.id', '=', 'stock_adjustment_details.stock_adjustment_id')
                ->join('warehouses', 'stock_adjustments.warehouse_id', '=', 'warehouses.id')
                ->select(
                    DB::raw('SUM(stock_adjustment_details.qty) as total_qty'),
                    DB::raw('YEAR(stock_adjustments.created_at) as year'),
                    DB::raw('MONTH(stock_adjustments.created_at) as month'),
                    'warehouses.name as warehouse_name',
                    'warehouses.id as warehouse_id'
                )
                ->groupBy('year', 'month', 'warehouse_id', 'warehouse_name')
                ->get();
            return $this->successResponse("Berhasil Mendapatkan Laporan Stok OP NAME", 200, [
                'data' => $inboundData
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

    public function productValueReport(Request $request)
    {
        $request->validate([
            'year' => 'sometimes|integer'
        ]);

        try {
            $inboundData = DB::table('inventory')
                ->join('product_skus', 'inventory.product_sku_id', '=', 'product_skus.id')
                ->join('warehouses', 'inventory.warehouse_id', '=', 'warehouses.id')
                ->select(
                    DB::raw('SUM(product_skus.price * inventory.qty) as total_item'),
                    DB::raw('SUM(inventory.qty) as total_qty'),
                    DB::raw('YEAR(inventory.created_at) as year'),
                    DB::raw('MONTH(inventory.created_at) as month'),
                    'warehouses.id as warehouse_id',
                    'warehouses.name as warehouse_name',
                    'product_skus.sku_number as sku_product_number'
                );

            if($request->has('year')){
                $inboundData->whereYear('created_at',$request->get('year'));
            }
            

            return $this->successResponse("Berhasil Mendapatkan Laporan Nilai Barang", 200, [
                'data' => $inboundData->groupBy('year', 'month', 'warehouse_id', 'sku_number')->get()
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
}

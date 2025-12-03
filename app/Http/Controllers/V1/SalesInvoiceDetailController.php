<?php

namespace App\Http\Controllers\V1;

use App\Enums\SalesInvoiceDetailProductTypeEnum;
use App\Enums\SalesInvoiceStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\SalesInvoiceDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Database\QueryException;
use Psr\Http\Client\NetworkExceptionInterface;

use function count;

class SalesInvoiceDetailController extends Controller
{
    public function grouppedSalesInvoiceDetail(Request $request)
    {
        $request->validate([
            'invoice_type' => 'required|string',
            'cashier_shift_detail_id' => 'nullable|integer'
        ]);

        try {
            $now = Carbon::now();

            $invoiceType = $request->get('invoice_type');
            $cashierShiftDetailId = $request->get('cashier_shift_detail_id');

            $salesInvoiceDetails = SalesInvoiceDetail::with([
                'salesInvoice' => function ($query) {
                    return $query->with(['leasing']);
                }
            ])
                ->whereDate('created_at', $now)
                ->where('deleted_at', null)
                ->whereHas('salesInvoice', function ($query) use ($invoiceType) {
                    return $query->where('type', $invoiceType)->where('status', '!=', SalesInvoiceStatusEnum::VOID)->where('is_in_paid', true);
                });


            if ($request->has('cashier_shift_detail_id')) {
                $salesInvoiceDetails->whereHas('shiftTransaction', function ($query) use ($cashierShiftDetailId) {
                    return $query->where('cs_detail_id', $cashierShiftDetailId);
                });
            }
            /**
             * Let's Group Sales Invoice Detail By Each Type
             */

            $salesInvoiceDetails = $salesInvoiceDetails->get();
            $data = [];

            foreach ($salesInvoiceDetails as $salesInvoiceDetail) {
                if (count($data) < 1) {
                    if ($salesInvoiceDetail->product_type === SalesInvoiceDetailProductTypeEnum::BARANG_LEASING) {
                        $data[] = [
                            'group' => $salesInvoiceDetail->salesInvoice->leasing->name,
                            'details' => [$salesInvoiceDetail],
                            'total' => $salesInvoiceDetail->sub_total
                        ];
                    } else {
                        $data[] = [
                            'group' => $salesInvoiceDetail->product_type,
                            'details' => [$salesInvoiceDetail],
                            'total' => $salesInvoiceDetail->sub_total
                        ];
                    }
                } else {
                    $key = array_column($data, 'group');
                    $column = array_search($salesInvoiceDetail->product_type === SalesInvoiceDetailProductTypeEnum::BARANG_LEASING ? $salesInvoiceDetail->salesInvoice->leasing->name : $salesInvoiceDetail->product_type, $key, false);

                    if ($column) {
                        $data[$column]['details'] = $salesInvoiceDetail;
                        $data[$column]['total'] += $salesInvoiceDetail->sub_total;
                    } else {
                        $data[] = [
                            'group' => $salesInvoiceDetail->product_type === SalesInvoiceDetailProductTypeEnum::BARANG_LEASING ? $salesInvoiceDetail->salesInvoice->leasing->name : $salesInvoiceDetail->product_type,
                            'details' => [$salesInvoiceDetail],
                            'total' => $salesInvoiceDetail->sub_total
                        ];
                    }
                }
            }

            return $this->successResponse("Berhasil Mendapatkan Detail Faktur Penjualan", 200, [
                'data' => $data
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
}

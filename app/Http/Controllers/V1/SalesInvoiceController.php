<?php

namespace App\Http\Controllers\V1;

use App\Enums\PermissionEnum;
use App\Enums\SalesInvoiceStatusEnum;
use App\Enums\SalesInvoiceTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\SalesInvoice;
use App\Models\Setting;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;

class SalesInvoiceController extends Controller
{
   public function index(Request $request)
   {
      $request->validate([
         'is_public' => 'boolean',
         'page' => 'required|integer',
         'per_page' => 'required|integer',
         'search' => 'string',
         'sort_by' => 'string',
         'order_by' => "string",
         'type' => 'string',
         'price_type' => 'string'
      ]);

      try {
         $user = auth()->user();
         if (!$user->hasPermissionTo(PermissionEnum::MELIHAT_SALES_INVOICE)) {
            return $this->errorResponse("Tidak Punya Hak Untuk Melihat Fitur Ini", 403, []);
         }

         $salesInvoices = SalesInvoice::with(['createdBy', 'leasing', 'updatedBy', 'voidBy'])->where('deleted_at', null);
         $perPage = $request->get('per_page');

         if ($request->has('search')) {
            $salesInvoices->where(function ($query) use ($request) {
               $search = $request->get('search');
               $query->where('code', 'like', "%$search%")
                  ->orWhere('customer_name', 'like', "%$search%")
                  ->orWhere('other_code', 'like', "%$search%");
            });
         }

         if ($request->has('type')) {
            $salesInvoices->where('type', $request->get('type'));
         }

         if ($request->has('price_type')) {
            $salesInvoices->where('price_type', $request->get('price_type'));
         }

         return $this->successResponse("Berhasil Mendapatkan Sales Invoice", 200, [
            'items' => $salesInvoices->paginate($perPage)
         ]);

      } catch (Exception $e) {
         return $this->errorResponse($e->getMessage(), 500, []);
      }
   }

   public function generate(Request $request)
   {
      $request->validate([
         'type' => 'required|string'
      ]);

      try {
         $user = auth()->user();
         if (!$user->hasPermissionTo(PermissionEnum::MELIHAT_DETAIL_SALES_INVOICE)) {
            return $this->errorResponse("Tidak Punya Hak Untuk Melihat Fitur Ini", 403, []);

         }
         $setting = Setting::firstOrFail();
         $salesInvoice = new SalesInvoice;

         $lastInvoiceNumber = $setting->tax_invoice_code + 1;

         $salesInvoice->date = Carbon::now();
         $salesInvoice->code = $lastInvoiceNumber;
         $salesInvoice->type = $request->get('type');
         $salesInvoice->created_by_id = $user->get('id');

         if ($request->get('type') === SalesInvoiceTypeEnum::PPN) {
            $setting->tax_invoice_code = $lastInvoiceNumber;
         } else {
            $setting->no_tax_invoice_code = $lastInvoiceNumber;
         }

         if ($salesInvoice->save()) {
            return $this->successResponse("Berhasil Menambahkan Sales Invoice", 200, [
               'data' => $salesInvoice,
            ]);
         }


      } catch (Exception $e) {
         return $this->errorResponse($e->getMessage(), 500, []);
      }
   }

   public function generateBulk(Request $request)
   {
      $request->validate([
         'number' => 'required|integer',
         'type' => 'required|string',
      ]);

      try {
         $user = auth()->user();
         if (!$user->hasPermissionTo(PermissionEnum::MEMBUAT_SALES_INVOICE)) {
            return $this->errorResponse("Tidak Punya Hak Untuk Melihat Fitur Ini", 403, []);

         }

         $now = Carbon::now();

         $year = $now->year;
         $month = $now->month;

         $createdSalesInvoices = [];
         $initialSIFormat = "SI.$year.$month";
         $initialNoTaxSIFormat = "JI.$year.$month";

         $setting = Setting::firstOrFail();

         if ($request->get('type') === SalesInvoiceTypeEnum::PPN) {
            $lastInvoiceNumber = $setting->tax_invoice_code;
            if ($now->day === 1) {
               $lastInvoiceNumber = 1;
            }
            $setting->tax_invoice_code = $lastInvoiceNumber;
            $salesInvoiceGenerated = [];

            for ($x = 0; $x < $request->get('number'); $x++) {
               $lastInvoiceNumber += 1;
               $initialSIFormat = $initialSIFormat . ".$lastInvoiceNumber";
               $salesInvoiceGenerated[] = [
                  'code' => $initialSIFormat,
                  'other_code' => $initialSIFormat,
                  'date' => $now,
                  'type' => SalesInvoiceTypeEnum::PPN,
                  'created_by_id' => $user->get(columns: 'id'),
               ];
            }

            $createdSalesInvoices = SalesInvoice::insert($salesInvoiceGenerated);
         } else {
            $lastInvoiceNumber = $setting->no_tax_invoice_code;
            $salesNonTaxInvoiceGenerated = [];
            $initialNonTaxSIFormat = $initialNoTaxSIFormat . ".$lastInvoiceNumber";
            for ($x = 0; $x < $request->get('number'); $x++) {
               $lastInvoiceNumber += 1;
               $salesNonTaxInvoiceGenerated[] = [
                  'code' => $initialNonTaxSIFormat,
                  'other_code' => $initialNonTaxSIFormat,
                  'date' => $now,
                  'type' => SalesInvoiceTypeEnum::NON_PPN,
                  'created_by_id' => $user->get(columns: 'id'),
               ];
            }

            $createdSalesInvoices = SalesInvoice::insert($salesNonTaxInvoiceGenerated);
         }

         if ($createdSalesInvoices) {
            return $this->successResponse("Berhasil Membuat Sales Invoice", 200, [
               'data' => $createdSalesInvoices
            ]);
         }

      } catch (Exception $e) {
         return $this->errorResponse($e->getMessage(), 500, []);
      }
   }

   public function create(Request $request)
   {
      $request->validate([
         'description' => 'string',
         'customer_name' => 'string',
         'warehouse' => 'string',
         'sales_person_name' => 'string',
         'type' => 'required|string',
         'price_type' => 'required|string',
         'grand_total' => 'required|integer',
         'sub_total' => 'required|integer',
         'leasing_id' => 'required|integer',
         'code' => 'required|string',
         'other_code' => 'required|string'
      ]);
      try {
         $user = auth()->user();
         if (!$user->hasPermissionTo(PermissionEnum::MEMBUAT_SALES_INVOICE)) {
            return $this->errorResponse("Tidak Punya Hak Untuk Melihat Fitur Ini", 403, []);
         }

         $salesInvoice = new SalesInvoice;

         $salesInvoice->customer_name = $request->get('customer_name');
         $salesInvoice->sales_person_name = $request->get('sales_person_name');
         $salesInvoice->warehouse = $request->get('warehouse');
         $salesInvoice->price_type = $request->get('price_type');
         $salesInvoice->type = $request->get('type');
         $salesInvoice->leasing_id = $request->get('leasing_id');
         $salesInvoice->created_by_id = $user->get('id');


      } catch (Exception $e) {
         return $this->errorResponse($e->getMessage(), 500, []);
      }
   }

   public function changeStatus()
   {
      try {
         $user = auth()->user();
         if (!$user->hasPermissionTo(PermissionEnum::MENYETUJUI_SALES_INVOICE)) {
            return $this->errorResponse("Tidak Punya Hak Untuk Melihat Fitur Ini", 403, []);
         }

      } catch (Exception $e) {
         return $this->errorResponse($e->getMessage(), 500, []);
      }
   }

   public function detail($id)
   {
      try {
         $user = auth()->user();
         if (!$user->hasPermissionTo(PermissionEnum::MELIHAT_DETAIL_SALES_INVOICE)) {
            return $this->errorResponse("Tidak Punya Hak Untuk Melihat Fitur Ini", 403, []);

         }

         $findSalesInvoice = SalesInvoice::with(['salesInvoiceDetails', 'voidBy', 'createdBy', 'updatedBy'])->where('deleted_at', null)->first();
         if (!$findSalesInvoice) {
            return $this->errorResponse("Sales Invoice Tidak Ditemukan", 404, []);
         }

         return $this->successResponse("Berhasil Mendapaktna Detail Sales Invoice", 200, [
            'data' => $findSalesInvoice
         ]);

      } catch (Exception $e) {
         return $this->errorResponse($e->getMessage(), 500, []);
      }
   }

   public function update($id, Request $request)
   {
      $request->validate([
         'description' => 'string',
         'customer_name' => 'string',
         'warehouse' => 'string',
         'sales_person_name' => 'string',
         'type' => 'required|string',
         'price_type' => 'required|string',
         'grand_total' => 'required|integer',
         'sub_total' => 'required|integer',
         'leasing_id' => 'required|integer',
      ]);

      try {
         $user = auth()->user();
         if (!$user->hasPermissionTo(PermissionEnum::MENGEDIT_SALES_INVOICE)) {
            return $this->errorResponse("Tidak Punya Hak Untuk Melihat Fitur Ini", 403, []);

         }
         $findSalesInvoice = SalesInvoice::with(['salesInvoiceDetails', 'voidBy', 'createdBy', 'updatedBy'])->where('deleted_at', null)->first();
         if (!$findSalesInvoice) {
            return $this->errorResponse("Sales Invoice Tidak Ditemukan", 404, []);
         }

         $findSalesInvoice->customer_name = $request->get('customer_name');
         $findSalesInvoice->sales_person_name = $request->get('sales_person_name');
         $findSalesInvoice->warehouse = $request->get('warehouse');
         $findSalesInvoice->price_type = $request->get('price_type');
         $findSalesInvoice->type = $request->get('type');
         $findSalesInvoice->leasing_id = $request->get('leasing_id');
         $findSalesInvoice->updated_by_id =$user->get('id');

         if ($findSalesInvoice->save()) {
            return $this->successResponse("Berhasil Mengedit Sales Invoice", 200, []);
         }

      } catch (Exception $e) {
         return $this->errorResponse($e->getMessage(), 500, []);
      }
   }

   public function destroy($id)
   {
      try {
         $user = auth()->user();
         if (!$user->hasPermissionTo(PermissionEnum::VOID_SALES_INVOICE)) {
            return $this->errorResponse("Tidak Punya Hak Untuk Melihat Fitur Ini", 403, []);

         }

         $findSI = SalesInvoice::where('deleted_at', null)->where('id', $id)->first();
         if (!$findSI) {
            return $this->errorResponse("Sales Invoice Tidak Ditemukan", 404, []);
         }

         $findSI->status = SalesInvoiceStatusEnum::VOID;
         $findSI->void_by_id = $user->get('id');

         if ($findSI->save()) {
            return $this->successResponse("Berhasil Mengubah Status Sales Invoice Menjadi Void", 200, [
               'data' => $findSI->fresh()
            ]);
         }

      } catch (Exception $e) {
         return $this->errorResponse($e->getMessage(), 500, []);
      }
   }//should be void function
}

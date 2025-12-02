<?php

namespace App\Http\Controllers\V1;

use App\Enums\PermissionEnum;
use App\Helper\SalesInvoiceNumberFormatter;
use App\Enums\SalesInvoiceStatusEnum;
use App\Enums\SalesInvoiceTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\Leasing;
use App\Models\PaymentMethod;
use App\Models\SalesInvoice;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\SalesInvoiceNotification;
use Carbon\Carbon;
use DB;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Log;
use Notification;
use Psr\Http\Client\NetworkExceptionInterface;
use Storage;
use function intval;
use function count;

class SalesInvoiceController extends Controller
{
   public function index(Request $request)
   {
      $request->validate([
         'page' => 'required|integer',
         'per_page' => 'required|integer',
      ]);

      try {
         $user = auth()->user();
         if (!$user->hasPermissionTo(PermissionEnum::MELIHAT_SALES_INVOICE) && !$request->has('is_public')) {
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

         if ($request->has('start_date') && $request->has('end_date')) {
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');

            $salesInvoices->whereBetween('created_at', [$startDate, $endDate]);
         }

         if ($request->has('type')) {
            $salesInvoices->where('type', $request->get('type'));
         }

         if ($request->has('price_type')) {
            $salesInvoices->where('price_type', $request->get('price_type'));
         }

         if($request->has('status')){
            $salesInvoices->where('status', $request->get('status'));
         }

         if ($request->has('sort_by') && $request->has('order_by')) {
            $salesInvoices->orderBy($request->get('order_by'), $request->get('sort_by'));
         }

         return $this->successResponse("Berhasil Mendapatkan Sales Invoice", 200, [
            'items' => $salesInvoices->paginate($perPage)
         ]);

      } catch (Exception $e) {
         return $this->errorResponse($e->getMessage(), 500, []);
      } catch (QueryException $eq) {
         return $this->errorResponse($eq->getMessage(), 500, []);
      } catch (NetworkExceptionInterface $nei) {
         return $this->errorResponse($nei->getMessage(), 500, []);
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
            $setting->tax_invoice_code = intval($lastInvoiceNumber);
         } else {
            $setting->no_tax_invoice_code = intval($lastInvoiceNumber);
         }

         if ($salesInvoice->save()) {
            return $this->successResponse("Berhasil Menambahkan Sales Invoice", 200, [
               'data' => $salesInvoice,
            ]);
         }


      } catch (Exception $e) {
         return $this->errorResponse($e->getMessage(), 500, []);
      } catch (QueryException $eq) {
         return $this->errorResponse($eq->getMessage(), 500, []);
      } catch (NetworkExceptionInterface $nei) {
         return $this->errorResponse($nei->getMessage(), 500, []);
      }
   }

   public function generateBulk(Request $request)
   {
      $request->validate([
         'number' => 'required|integer',
         'type' => 'required|string',
         'price_type' => 'required|string'
      ]);

      try {
         DB::beginTransaction();

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

         $setting = Setting::where('deleted_at', null)->first();

         if ($request->input('type') === "PPN") {
            if (!$setting || !$setting->get('tax_invoice_code')) {
               return $this->errorResponse("Harap Melengkapi Terlebih Dahulu Nomor Invoice Terakhir Pada Laman Setting", 400, []);
            }
            $findLatestSI = SalesInvoice::whereDate('created_at', $now)->where('deleted_at', null)->where('status', '!=', SalesInvoiceStatusEnum::VOID)->orderBy('id', 'desc')->first();

            $lastInvoiceNumber = intval($setting->tax_invoice_code);
            $salesInvoiceGenerated = [];

            if ($findLatestSI) {
               $splitLatestSICode = explode(".", $findLatestSI->code, PHP_INT_MAX);
               $lastInvoiceNumber = intval($splitLatestSICode[2]);
            } else {
               $lastInvoiceNumber = 1;
            }

            if ($now->day === 1) {
               $lastInvoiceNumber = 1;
            }

            for ($x = 0; $x < $request->input('number'); $x++) {
               $lastInvoiceFormatNumber = SalesInvoiceNumberFormatter::formatter("PPN", $lastInvoiceNumber);
               $currentInvoiceNumber = $initialSIFormat . ".$lastInvoiceFormatNumber";
               $salesInvoiceGenerated[] = [
                  'code' => $currentInvoiceNumber,
                  'other_code' => $currentInvoiceNumber,
                  'date' => $now,
                  'price_type' => $request->get('price_type'),
                  'status' => SalesInvoiceStatusEnum::PERLU_DILENGKAPI,
                  'type' => SalesInvoiceTypeEnum::PPN,
                  'created_by_id' => $user->id,
                  'created_at' => $now
               ];
               $lastInvoiceNumber += 1;
            }

            $createdSalesInvoices = SalesInvoice::insert($salesInvoiceGenerated);
            $setting->tax_invoice_code = intval($lastInvoiceNumber);
         } else {
            if (!$setting || !$setting->get('no_tax_invoice_code')) {
               return $this->errorResponse("Harap Melengkapi Terlebih Dahulu Nomor Invoice Terakhir Pada Laman Setting", 400, []);
            }
            $lastInvoiceNumber = intval($setting->no_tax_invoice_code);

            $salesNonTaxInvoiceGenerated = [];
            for ($x = 0; $x < $request->get('number'); $x++) {
               $lastInvoiceFormatNumber = SalesInvoiceNumberFormatter::formatter("NON PPN", $lastInvoiceNumber);
               $currentInvoiceNumber = $initialNoTaxSIFormat . ".$lastInvoiceFormatNumber";
               $salesNonTaxInvoiceGenerated[] = [
                  'code' => $currentInvoiceNumber,
                  'other_code' => $currentInvoiceNumber,
                  'status' => SalesInvoiceStatusEnum::PERLU_DILENGKAPI,
                  'date' => $now,
                  'price_type' => $request->get('price_type'),
                  'type' => SalesInvoiceTypeEnum::NON_PPN,
                  'created_by_id' => $user->id,
                  'created_at' => $now,
               ];
               $lastInvoiceNumber += 1;
            }

            $createdSalesInvoices = SalesInvoice::insert($salesNonTaxInvoiceGenerated);
            $setting->no_tax_invoice_code = intval($lastInvoiceNumber);
         }

         DB::commit();

         if ($createdSalesInvoices && $setting->save()) {
            return $this->successResponse("Berhasil Membuat Sales Invoice", 200, []);
         }

      } catch (Exception $e) {
         DB::rollBack();
         return $this->errorResponse($e->getMessage(), 500, []);
      } catch (QueryException $eq) {
         DB::rollBack();
         return $this->errorResponse($eq->getMessage(), 500, []);
      } catch (NetworkExceptionInterface $nei) {
         DB::rollBack();
         return $this->errorResponse($nei->getMessage(), 500, []);
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
      } catch (QueryException $eq) {
         return $this->errorResponse($eq->getMessage(), 500, []);
      } catch (NetworkExceptionInterface $nei) {
         return $this->errorResponse($nei->getMessage(), 500, []);
      }
   }

   public function changeStatus(Request $request, $id)
   {
      $request->validate([
         'status' => 'required',
      ]);

      try {
         DB::beginTransaction();

         $user = auth()->user();
         $salesInvoiceCode = $request->get('sales_invoice_code');

         $findPaymentMethod = null;
         $findLeasing = null;
         $findSalesInvoice = SalesInvoice::where('deleted_at', null)->where('id', $id)->first();

         if (!$findSalesInvoice) {
            return $this->errorResponse("Sales Invoice Tidak Ditemukan", 404, []);
         }

         if ($request->has('sales_invoice_code') && $findSalesInvoice->code !== $salesInvoiceCode) {
            return $this->errorResponse("Nomor Invoice Tidak Sesuai", 400, []);
         }

         if (!$user->hasPermissionTo(PermissionEnum::MENYETUJUI_SALES_INVOICE)) {
            return $this->errorResponse("Tidak Punya Hak Untuk Melihat Fitur Ini", 403, []);
         }

         if ($request->get('status') === 'MEMERLUKAN PERSETUJUAN PIUTANG') {
            $findPaymentMethod = PaymentMethod::where('deleted_at', null)->where('id', $request->get('payment_method_id'))->first();
            $findLeasing = Leasing::where('deleted_at', null)->where('id', $request->get('leasing_id'))->first();
            $findUserByRole = User::whereHas('roles', function ($query) {
               return $query->whereIn('name', ['Supervisor', 'Owner']);
            })->get();

            if ($request->has('description')) {
               $findSalesInvoice->receiveable_approval_note = $request->get('description');
            }

            if (str_contains(strtolower($findPaymentMethod->name), "kredit")) {
               Notification::send($findUserByRole, new SalesInvoiceNotification(
                  "Faktur Dengan Kode $findSalesInvoice->code Memerlukan Persetujuan Dari Pihak Berwenang Untuk Menjadi Piutang",
                  "UPDATE",
                  "Persetujuan Piutang",
                  "Faktur Dengan Kode $findSalesInvoice->code Memerlukan Persetujuan Dari Pihak Berwenang Untuk Menjadi Piutang",
                  SalesInvoiceStatusEnum::MEMERLUKAN_PERSETUJUAN_PIUTANG,
                  "URGENT",
                  $user->id,
                  $findSalesInvoice->code,
                  $findSalesInvoice->id
               ));
            } else {
               Notification::send($findUserByRole, new SalesInvoiceNotification(
                  "Faktur Dengan Kode $findSalesInvoice->code Memerlukan Persetujuan Dari Pihak Berwenang Untuk Menjadi Piutang Untuk Leasing $findLeasing->name",
                  "UPDATE",
                  "Persetujuan Piutang Leasing",
                  "Faktur Dengan Kode $findSalesInvoice->code Memerlukan Persetujuan Dari Pihak Berwenang Untuk Menjadi Piutang Untuk Leasing $findLeasing->name",
                  SalesInvoiceStatusEnum::MEMERLUKAN_PERSETUJUAN_PIUTANG,
                  "URGENT",
                  $user->id,
                  $findSalesInvoice->code,
                  $findSalesInvoice->id
               ));
            }
            $findSalesInvoice->status = SalesInvoiceStatusEnum::MEMERLUKAN_PERSETUJUAN_PIUTANG;
         } else {
            $findUserByRole = User::whereHas('roles', callback: function ($query) {
               return $query->where('name', 'Kasir');
            })->first();
            if ($findUserByRole) {
               Notification::send($findUserByRole, new SalesInvoiceNotification("Faktur Penjualan Dengan Kode $salesInvoiceCode Disetujui Menjadi Piutang", "UPDATE", "PERUBAHAN SALES INVOICE", $findSalesInvoice->status, "URGENT", $user->id, $salesInvoiceCode, $findSalesInvoice->code, $findSalesInvoice->id));
            }
            $findSalesInvoice->status = SalesInvoiceStatusEnum::DISETUJUI_MENJADI_PIUTANG;
         }


         $findSalesInvoice->save();

         DB::commit();

         return $this->successResponse("Berhasil Mengubah Status Sales Invoice", 200, [
            'data'=> $findSalesInvoice->fresh()
         ]);
      } catch (Exception $e) {
         DB::rollBack();
         return $this->errorResponse($e->getMessage(), 500, []);
      } catch (QueryException $eq) {
         DB::rollBack();
         return $this->errorResponse($eq->getMessage(), 500, []);
      } catch (NetworkExceptionInterface $nei) {
         DB::rollBack();
         return $this->errorResponse($nei->getMessage(), 500, []);
      }
   }

   public function detail($id)
   {
      try {
         $user = auth()->user();
         if (!$user->hasPermissionTo(PermissionEnum::MELIHAT_DETAIL_SALES_INVOICE)) {
            return $this->errorResponse("Tidak Punya Hak Untuk Melihat Fitur Ini", 403, []);

         }

         $findSalesInvoice = SalesInvoice::with([
            'salesInvoiceDetails' => function ($query) {
               return $query->where('deleted_at', null);
            },
            'voidBy',
            'createdBy',
            'updatedBy'
         ])->where('deleted_at', null)->where('id', $id)->first();
         if (!$findSalesInvoice) {
            return $this->errorResponse("Sales Invoice Tidak Ditemukan", 404, []);
         }

         return $this->successResponse("Berhasil Mendapaktna Detail Sales Invoice", 200, [
            'data' => $findSalesInvoice
         ]);

      } catch (Exception $e) {
         return $this->errorResponse($e->getMessage(), 500, []);
      } catch (QueryException $eq) {
         return $this->errorResponse($eq->getMessage(), 500, []);
      } catch (NetworkExceptionInterface $nei) {
         return $this->errorResponse($nei->getMessage(), 500, []);
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
         'code' => 'required|string',
      ]);

      try {
         DB::beginTransaction();

         $user = auth()->user();
         $now = Carbon::now();

         if (!$user->hasPermissionTo(PermissionEnum::MENGEDIT_SALES_INVOICE)) {
            return $this->errorResponse("Tidak Punya Hak Untuk Melihat Fitur Ini", 403, []);

         }
         $findSalesInvoice = SalesInvoice::with(['salesInvoiceDetails', 'voidBy', 'createdBy', 'updatedBy'])->where('deleted_at', null)->where('id', $id)->first();
         if (!$findSalesInvoice) {
            return $this->errorResponse("Sales Invoice Tidak Ditemukan", 404, []);
         }

         if ($findSalesInvoice->code !== $request->get('code')) {
            return $this->errorResponse("Nomor Invoice Tidak Sesuai", 400, []);
         }

         $findSalesInvoice->customer_name = $request->get('customer_name');
         $findSalesInvoice->sales_person_name = $request->get('sales_person_name');
         $findSalesInvoice->warehouse = $request->get('warehouse');
         $findSalesInvoice->price_type = $request->get('price_type');
         $findSalesInvoice->type = $request->get('type');
         if ($request->has('leasing_id')) {
            $findSalesInvoice->leasing_id = $request->get('leasing_id');
         }
         $findSalesInvoice->updated_by_id = $user->id;
         $findSalesInvoice->grand_total = intval($request->get('grand_total')) + intval($request->get('tax_amount'));
         $findSalesInvoice->sub_total = $request->get('sub_total');
         $findSalesInvoice->tax_value = $request->get('tax_amount');
         $findSalesInvoice->tax = 11;
         $findSalesInvoice->discount = $request->get('discount');

         $findSalesInvoice->grand_total_left = (intval($request->get('sub_total')) - $request->get('discount')) + intval($request->get('tax_amount'));
         $findSalesInvoice->grand_total_left += intval($request->get('other_fee'));

         if ($request->hasFile('file')) {
            $fileExt = $request->file()->extension();
            $fileOriginName = $request->file()->getFilename();
            $previousFilePath = $findSalesInvoice->file_path;
            $fileName = "$now->year" . "-" . "$now->month" . "-" . "$findSalesInvoice->code" . "-" . "$fileOriginName" . ".$fileExt";

            if (Storage::disk('public')->exists("uploads/$previousFilePath")) {
               Storage::disk('public')->delete("uploads/$previousFilePath");
            }

            $request->file()->storeAs('uploads', $fileName, 'public');
         }

         if ($findSalesInvoice->grand_total > 0) {
            $findSalesInvoice->status = SalesInvoiceStatusEnum::BELUM_LUNAS;
         }

         if ($request->has('sales_invoice_details')) {
            $salesInvoiceDetails = $request->get('sales_invoice_details');
            $findSalesInvoice->salesInvoiceDetails()->delete();
            $findSalesInvoice->salesInvoiceDetails()->insert($salesInvoiceDetails);
         }

         $findSalesInvoice->save();

         DB::commit();

         return $this->successResponse("Berhasil Mengedit Sales Invoice", 200, []);

      } catch (Exception $e) {
         DB::rollBack();
         return $this->errorResponse($e->getMessage(), 500, []);
      } catch (QueryException $eq) {
         DB::rollBack();
         return $this->errorResponse($eq->getMessage(), 500, []);
      } catch (NetworkExceptionInterface $nei) {
         DB::rollBack();
         return $this->errorResponse($nei->getMessage(), 500, []);
      }
   }

   public function destroy($id, Request $request)
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
         $findSI->void_note = $request->get('void_note');

         if ($findSI->save()) {
            return $this->successResponse("Berhasil Mengubah Status Sales Invoice Menjadi Void", 200, [
               'data' => $findSI->fresh()
            ]);
         }

      } catch (Exception $e) {
         return $this->errorResponse($e->getMessage(), 500, []);
      } catch (QueryException $eq) {
         return $this->errorResponse($eq->getMessage(), 500, []);
      } catch (NetworkExceptionInterface $nei) {
         return $this->errorResponse($nei->getMessage(), 500, []);
      }
   }//should be void function
}

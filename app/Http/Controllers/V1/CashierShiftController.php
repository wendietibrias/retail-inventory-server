<?php

namespace App\Http\Controllers\V1;

use App\Enums\PermissionEnum;
use App\Enums\SalesInvoiceTypeEnum;
use App\Enums\ShiftStatusEnum;
use App\Enums\ShiftTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\CashierShift;
use App\Models\CashierShiftDetail;
use App\Models\PaymentType;
use App\Models\TransactionSummarize;
use App\Models\TransactionSummarizeDetail;
use App\Models\TransactionSummarizeDetailpayment;
use Carbon\Carbon;
use DB;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

use Psr\Http\Client\NetworkExceptionInterface;
use function count;

class CashierShiftController extends Controller
{

    public function index(Request $request)
    {
        $request->validate([
            'page' => 'required|integer',
            'perPage' => 'required|integer',
        ]);

        try {
            $user = auth()->user();
            $page = $request->get('page');
            $perPage = $request->get('per_page');
            $search = $request->get('search');
            $findAllShift = CashierShift::with(['cashierShiftDetails','createdBy'])->where('deleted_at', null);

            if(!$user->hasPermissionTo(PermissionEnum::MELIHAT_SHIFT)){
                return $this->errorResponse("Tidak Memiliki Hak Untuk Melihat Fitur Ini",403,[]);
            }

            if ($request->has('search')) {
                $findAllShift->where('code', 'like', "%$search%");
            }

            return $this->successResponse("Berhasil Mendapatkan Data Shift", 200, [
                'items' => $findAllShift->paginate($perPage)
            ]);

        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500, []);
        }
    }

    public function create(Request $request)
    {
        try {
            DB::beginTransaction();

            $user = auth()->user();
            $now = Carbon::now();

            $month = $now->month;
            $year = $now->year;

            $findAllPaymentDetails = PaymentType::all();
            $findCurrentShiftIfExist = CashierShift::whereDate('created_at', $now)->first();
            $findLatestShift = CashierShift::where('deleted_at', null)->orderBy('id', 'desc')->first();

            if ($findCurrentShiftIfExist) {
                return $this->errorResponse("Terdapat Shift Yang Sudah Dibuat Pada Hari Ini", 400, []);
            }

            $latestShift = $findLatestShift ? $findLatestShift->id + 1 : 1;
            $formattedLatestShiftLastCount = null;

            if ($latestShift < 10) {
                $formattedLatestShiftLastCount = "000$latestShift";
            }
            if ($latestShift >= 10 && $latestShift < 100) {
                $formattedLatestShiftLastCount = "00$latestShift";
            }
            if ($latestShift >= 100 && $latestShift < 1000) {
                $formattedLatestShiftLastCount = "0$latestShift";
            }
            if ($latestShift >= 1000 && $latestShift <= 10000) {
                $formattedLatestShiftLastCount = "$latestShift";
            }

            /**
             * Create Shift and the details
             */
            $createShift = CashierShift::create([
                'code' => "SHIFT/$year/$month/$formattedLatestShiftLastCount",
                'created_by'=> $user->id
            ]);

            $createShift->cashierShiftDetails()->insert([
                [
                    'created_at' => $now,
                    'type' => ShiftTypeEnum::PAGI,
                    'status' => ShiftStatusEnum::BELUM_MULAI,
                ],
                [
                    'created_at' => $now,
                    'type' => ShiftTypeEnum::MALAM,
                    'status' => ShiftStatusEnum::BELUM_MULAI,
                ]
            ]);

            $createShift->save();

            /** Create Transaction Summarize and the details */
            $createTransactionSummarize = TransactionSummarize::create([
                'cashier_shift_id' => $createShift->fresh()->id,
            ]);

            $createTransactionSummarize->save();

            $createTransactionSummarize = $createTransactionSummarize->fresh();

            $parentId = $createTransactionSummarize->id;

            $dataDetails = [
                [
                    'transaction_summarize_id' => $parentId, // Wajib manual untuk insert()
                    'created_at' => $now,
                    'updated_at' => $now, // insert() tidak otomatis mengisi updated_at
                    'invoice_type' => SalesInvoiceTypeEnum::PPN,
                    'shift_type' => ShiftTypeEnum::PAGI,
                ],
                [
                    'transaction_summarize_id' => $parentId,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'invoice_type' => SalesInvoiceTypeEnum::NON_PPN,
                    'shift_type' => ShiftTypeEnum::PAGI,
                ],
                [
                    'transaction_summarize_id' => $parentId,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'invoice_type' => SalesInvoiceTypeEnum::PPN,
                    'shift_type' => ShiftTypeEnum::MALAM,
                ],
                [
                    'transaction_summarize_id' => $parentId,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'invoice_type' => SalesInvoiceTypeEnum::NON_PPN,
                    'shift_type' => ShiftTypeEnum::MALAM,
                ],
            ];


            TransactionSummarizeDetail::insert($dataDetails);

            $createTransactionSummarize->load('transactionSummarizeDetails');

            $payloadPaymentShiftDetail = [];

            foreach($createTransactionSummarize->transactionSummarizeDetails as $detailItem){
                 foreach($findAllPaymentDetails as $paymentDetail){
                    $payloadPaymentShiftDetail[] =[
                        'tsd_id'=> $detailItem->id,
                        'pm_detail_id'=>$paymentDetail->id,
                        'total_payment' => 0,
                        'admin_fee' => 0,
                        'total_tax' => 0
                    ];
                 }
            }

            TransactionSummarizeDetailpayment::insert($payloadPaymentShiftDetail);

            DB::commit();

            return $this->successResponse("Berhasil Membuat Shift Kasir", 200,[
                'data'=>$createTransactionSummarize
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage(), $e->getCode(), []);

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

    public function detail($id)
    {
        try {
            $findShift = CashierShift::with([
                'cashierShiftDetails' => function ($query) {
                    return $query->with(['shiftTransactions', 'cashier']);
                }
            ])->where('id',$id)->where('deleted_at', null)->first();

            if (!$findShift) {
                return $this->errorResponse("Shift Tidak Ditemukan", 404, []);
            }

            return $this->successResponse("Berhasil Mendapatkan Data Shift", 200, [
                'data' => $findShift
            ]);

        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500, []);
        }
    }

    public function openShift(Request $request,$id)
    {
        $request->validate([
            'initial_cash'=>'required|integer',
            'cash_drawer_amount' => 'required|string',
        ]);
        try {
            DB::beginTransaction();

            $now = Carbon::now();
            $user = auth()->user();

            $findShift = CashierShiftDetail::where('deleted_at',null)->where('id',$id)->first();
            if(!$findShift){
                return $this->errorResponse("Cashier Shift Tidak Ditemukan", 404,[]);
            }

            if($findShift->type === ShiftTypeEnum::MALAM){
                $findTransactionDetailPreviousShift = TransactionSummarizeDetail::where('deleted_at',null)->where('shift_type', ShiftTypeEnum::PAGI);
                $findTransactionDetailPreviousShiftNoPpn = TransactionSummarizeDetail::where('deleted_at', null);
                $findPreviousShift = CashierShiftDetail::where('deleted_at',null)->whereDate('created_at', $now)->where('type', ShiftTypeEnum::PAGI)->first();
                if($findPreviousShift){
                     
                }
            }

            $findShift->status = ShiftStatusEnum::SEDANG_BERLANGSUNG;
            $findShift->shift_open_time = $now->timestamp;
            $findShift->cashier_id = $user->id;
            $findShift->initial_cash_amount = $request->get('initial_cash_amount');

            if($findShift->save()){
                return $this->successResponse("Berhasil Membuka Shift", 200,[]);
            }

            DB::commit();
        }catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500, []);

        } catch (QueryException $qeq) {
            if ($qeq->getCode() === '23000' || str_contains($qeq->getMessage(), 'Integrity constraint violation')) {
                return $this->errorResponse( 'Gagal menghapus! Data ini masih memiliki relasi aktif di tabel lain. Harap hapus relasi terkait terlebih dahulu.',500,[]);
            }
            return $this->errorResponse("Internal Server Error", 500, []);
        } catch (NetworkExceptionInterface $nei) {
            return $this->errorResponse($nei->getMessage(), 500, []);
        }
    }

    public function closeShift(Request $request,$id){
        $request->validate([
            'cash_in_box'=>'required|integer',
        ]);

        try {
            $now = Carbon::now();
            $user = auth()->user();

            $findShift = CashierShiftDetail::where('deleted_at',null)->where('id',$id)->first();
            if(!$findShift){
                return $this->errorResponse("Cashier Shift Tidak Ditemukan", 404,[]);
            }

            $findShift->status = ShiftStatusEnum::SELESAI;
            $findShift->shift_close_time = $now->timestamp;
            $findShift->cashier_id = $user->id;
            $findShift->cash_in_box_amount = $request->get('cash_in_box_amount');
            $findShift->cash_drawer_amount = $request->get('cash_drawer_amount');


        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500, []);

        } catch (QueryException $qeq) {
            if ($qeq->getCode() === '23000' || str_contains($qeq->getMessage(), 'Integrity constraint violation')) {
                return $this->errorResponse( 'Gagal menghapus! Data ini masih memiliki relasi aktif di tabel lain. Harap hapus relasi terkait terlebih dahulu.',500,[]);
            }
            return $this->errorResponse("Internal Server Error", 500, []);
        } catch (NetworkExceptionInterface $nei) {
            return $this->errorResponse($nei->getMessage(), 500, []);
        }
    }

}

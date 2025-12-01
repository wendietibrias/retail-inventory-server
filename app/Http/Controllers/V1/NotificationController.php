<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Psr\Http\Client\NetworkExceptionInterface;

class NotificationController extends Controller
{
    public function index(Request $request){
        try {
           $user = auth()->user();
        //    $perPage = $request->get('per_page');
           $getNotification = $user->notifications()->where('read_at',null)->orderBy('created_at','desc')->with(['sender'])->paginate(10);
           
           return $this->successResponse("Berhasil Mendapatkan Notifasi", 200,['items' => $getNotification]);

        }  catch (Exception $e) {
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

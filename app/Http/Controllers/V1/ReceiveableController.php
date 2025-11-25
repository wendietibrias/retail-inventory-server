<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\Receiveable;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Psr\Http\Client\NetworkExceptionInterface;

class ReceiveableController extends Controller
{
    public function index(Request $request){
        $request->validate([
            'page' => 'required|integer',
            'per_page' => 'required|integer'
        ]);

        try {
          $perPage = $request->get('per_page');
          $search = $request->get('search');

          $findReceiveable = Receiveable::where('deleted_at',null);

          if($request->has('search')){
            $findReceiveable->where(function($query) use ($search) {
               return $query->where("code", "like", "%$search%");
            });
          }

          return $this->successResponse("Berhasil Mendapatkan Data Piutang", 200 , [
            'items' => $findReceiveable->paginate($perPage)
          ]);

        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500, []);

        } catch (QueryException $qeq) {
            if ($qeq->getCode() === '23000' || str_contains($qeq->getMessage(), 'Integrity constraint violation')) {
                return $this->errorResponse('Gagal menghapus! Data ini masih memiliki relasi aktif di tabel lain. Harap hapus relasi terkait terlebih dahulu.',500,[]);
            }
            return $this->errorResponse("Internal Server Error", 500, []);
        } catch (NetworkExceptionInterface $nei) {
            return $this->errorResponse($nei->getMessage(), 500, []);
        }
    }

    public function detail($id){
        try {
          $findReceiveable = Receiveable::where('deleted_at',null)->where('id',$id)->first();
          if(!$findReceiveable){
            return $this->errorResponse("Piutang Tidak Ditemukan",404,[]);
          }

          return $this->successResponse("Berhasil Mendapaktan Data Piutang",200, ['data' => $findReceiveable]);

        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500, []);

        } catch (QueryException $qeq) {
            if ($qeq->getCode() === '23000' || str_contains($qeq->getMessage(), 'Integrity constraint violation')) {
                return $this->errorResponse('Gagal menghapus! Data ini masih memiliki relasi aktif di tabel lain. Harap hapus relasi terkait terlebih dahulu.',500,[]);
            }
            return $this->errorResponse("Internal Server Error", 500, []);
        } catch (NetworkExceptionInterface $nei) {
            return $this->errorResponse($nei->getMessage(), 500, []);
        }
    }


}

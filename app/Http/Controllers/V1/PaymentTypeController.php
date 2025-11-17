<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\PaymentType;
use Exception;
use Illuminate\Http\Request;

class PaymentTypeController extends Controller
{
        public function index(Request $request)
    {
        try {
            $page = $request->get('page');
            $perPage = $request->get('perPage');

            $findAllPaymentType = PaymentType::where('deleted_at', null)->paginate($perPage);

            return $this->successResponse("Berhasil Mendapatkan Data Payment Type", 200, [
                'items' => $findAllPaymentType,
            ]);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode(), []);

        }
    }

    public function create(Request $request)
    {
        $request->validate([
            'name'=>'required|string',
            'code'=>'required|string',
            'description'=>'string'
        ]);

        try {

            $PaymentType = new PaymentType;

            $PaymentType->name = $request->get('name');
            $PaymentType->code = $request->get('code');
            $PaymentType->description = $request->get('description');

            if ($PaymentType->save()) {
                return $this->successResponse("Berhasil Menambahkan Payment Type", 200, [
                    'data' => $PaymentType
                ]);
            }

        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode(), []);
        }
    }

    public function destroy($id)
    {
        try {
            $findPaymentType = PaymentType::find($id);
            if (!$findPaymentType) {
                return $this->errorResponse("Payment Type Tidak Ditemukan");
            }

            if ($findPaymentType->delete()) {
                return $this->successResponse("Berhasil Menghapus Payment Type", 200, [
                    'data' => $findPaymentType
                ]);
            }

        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode(), []);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name'=>'required|string',
            'code'=>'required|string',
            'description'=>'string'
        ]);

        try {
            $findPaymentType = PaymentType::find($id);
            if (!$findPaymentType) {
                return $this->errorResponse("Payment Type Tidak Ditemukan");
            }

            $findPaymentType->name = $request->get('name');
            $findPaymentType->code = $request->get('code');
            $findPaymentType->description = $request->get('description');

            if ($findPaymentType->save()) {
                return $this->successResponse("Berhasil Mengedit Payment Type", 200, [
                    'data' => $findPaymentType
                ]);
            }

        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode(), []);
        }
    }

    public function detail($id)
    {
        try {
            $findPaymentType = PaymentType::find($id);
            if (!$findPaymentType) {
                return $this->errorResponse("Payment Type Tidak Ditemukan");
            }

            return $this->successResponse("Berhasil Mendapatkan Data Payment Type", 200, [
                'data' => $findPaymentType
            ]);

        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode(), []);
        }
    }
}

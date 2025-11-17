<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Exception;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
       public function index(Request $request)
    {
        try {
            $page = $request->get('page');
            $perPage = $request->get('perPage');

            $findAllPaymentMethod = PaymentMethod::where('deleted_at', null)->paginate($perPage);

            return $this->successResponse("Berhasil Mendapatkan Data Payment Method", 200, [
                'items' => $findAllPaymentMethod,
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

            $paymentMethod = new PaymentMethod;

            $paymentMethod->name = $request->get('name');
            $paymentMethod->code = $request->get('code');
            $paymentMethod->description = $request->get('description');

            if ($paymentMethod->save()) {
                return $this->successResponse("Berhasil Menambahkan Payment Method", 200, [
                    'data' => $paymentMethod
                ]);
            }

        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode(), []);
        }
    }

    public function destroy($id)
    {
        try {
            $findPaymentMethod = PaymentMethod::find($id);
            if (!$findPaymentMethod) {
                return $this->errorResponse("Leasing Tidak Ditemukan");
            }

            if ($findPaymentMethod->delete()) {
                return $this->successResponse("Berhasil Menghapus PaymentMethod", 200, [
                    'data' => $findPaymentMethod
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
            $findPaymentMethod = PaymentMethod::find($id);
            if (!$findPaymentMethod) {
                return $this->errorResponse("Leasing Tidak Ditemukan");
            }

            $findPaymentMethod->name = $request->get('name');
            $findPaymentMethod->code = $request->get('code');
            $findPaymentMethod->description = $request->get('description');

            if ($findPaymentMethod->save()) {
                return $this->successResponse("Berhasil Mengedit Payment Method", 200, [
                    'data' => $findPaymentMethod
                ]);
            }

        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode(), []);
        }
    }

    public function detail($id)
    {
        try {
            $findPaymentMethod = PaymentMethod::find($id);
            if (!$findPaymentMethod) {
                return $this->errorResponse("Payment Method Tidak Ditemukan");
            }

            return $this->successResponse("Berhasil Mendapatkan Data Payment Method", 200, [
                'data' => $findPaymentMethod
            ]);

        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode(), []);
        }
    }
}

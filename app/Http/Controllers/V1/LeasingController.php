<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateLeasingRequest;
use App\Http\Requests\UpdateLeasingRequest;
use App\Models\Leasing;
use Exception;
use Illuminate\Http\Request;

class LeasingController extends Controller
{
    public function index(Request $request)
    {
        try {
            $page = $request->get('page');
            $perPage = $request->get('perPage');

            $findAllLeasing = Leasing::where('deleted_at', null)->paginate();

            return $this->successResponse("Berhasil Mendapatkan Data Leasing", 200, [
                'items' => $findAllLeasing,
            ]);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode(), []);

        }
    }

    public function create(CreateLeasingRequest $createLeasingRequest)
    {
        try {
            $createLeasingRequest->validated();

            $leasing = new Leasing;

            $leasing->name = $createLeasingRequest->get('name');
            $leasing->code = $createLeasingRequest->get('code');
            $leasing->description = $createLeasingRequest->get('description');

            if ($leasing->save()) {
                return $this->successResponse("Berhasil Menambahkan Leasing", 200, [
                    'data' => $leasing
                ]);
            }

        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode(), []);
        }
    }

    public function destroy($id)
    {
        try {
            $findLeasing = Leasing::find($id);
            if (!$findLeasing) {
                return $this->errorResponse("Leasing Tidak Ditemukan");
            }

            if ($findLeasing->delete()) {
                return $this->successResponse("Berhasil Menghapus Leasing", 200, [
                    'data' => $findLeasing
                ]);
            }

        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode(), []);
        }
    }

    public function update(UpdateLeasingRequest $updateLeasingRequest, $id)
    {
        try {
            $findLeasing = Leasing::find($id);
            if (!$findLeasing) {
                return $this->errorResponse("Leasing Tidak Ditemukan");
            }

            $findLeasing->name = $updateLeasingRequest->get('name');
            $findLeasing->code = $updateLeasingRequest->get('code');
            $findLeasing->description = $updateLeasingRequest->get('description');

            if ($findLeasing->save()) {
                return $this->successResponse("Berhasil Mengedit Leasing", 200, [
                    'data' => $findLeasing
                ]);
            }

        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode(), []);
        }
    }

    public function detail($id)
    {
        try {
            $findLeasing = Leasing::find($id);
            if (!$findLeasing) {
                return $this->errorResponse("Leasing Tidak Ditemukan");
            }

            return $this->successResponse("Berhasil Mendapatkan Data Leasing", 200, [
                'data' => $findLeasing
            ]);

        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode(), []);
        }
    }
}

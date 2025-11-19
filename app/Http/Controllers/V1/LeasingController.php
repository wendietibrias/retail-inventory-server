<?php

namespace App\Http\Controllers\V1;

use App\Enums\PermissionEnum;
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
        $request->validate([
            'page' => 'required|integer',
            'perPage' => 'required|integer',
            'search' => 'string',
            'is_public' => 'boolean'
        ]);

        try {
            $user = auth()->user();

            if (!$user->hasPermissionTo(PermissionEnum::MELIHAT_LEASING) && !$request->has('is_public')) {
                return $this->errorResponse("Tidak Punya Hak Akses Untuk Melihat Fitur Ini", 403, []);
            }

            $page = $request->get('page');
            $perPage = $request->get('perPage');

            $findAllLeasing = Leasing::where('deleted_at', null);

            if ($request->has('search')) {
                $findAllLeasing->where(function ($query) use ($request) {
                    return $query->orWhere('name', 'like', "%$request->get('search')%")
                        ->orWhere('code', 'like', "%$request->get('search')%");
                });
            }

            if ($request->has('sortBy') && $request->has('orderBy')) {
                $findAllLeasing->orderBy($request->get('orderBy'), $request->get('sortBy'));
            }

            return $this->successResponse("Berhasil Mendapatkan Data Leasing", 200, [
                'items' => $findAllLeasing->paginate($perPage),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode(), []);

        }
    }

    public function create(CreateLeasingRequest $createLeasingRequest)
    {
        $createLeasingRequest->validated();
        try {

            $user = auth()->user();

            if (!$user->hasPermissionTo(PermissionEnum::MENAMBAH_LEASING)) {
                return $this->errorResponse("Tidak Punya Hak Akses Untuk Melihat Fitur Ini", 403, []);
            }

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
            $user = auth()->user();
            if (!$user->hasPermissionTo(PermissionEnum::MENGHAPUS_LEASING)) {
                return $this->errorResponse("Tidak Punya Hak Akses Untuk Melihat Fitur Ini", 403, []);
            }
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
            $user = auth()->user();
            if (!$user->hasPermissionTo(PermissionEnum::MENGEDIT_LEASING)) {
                return $this->errorResponse("Tidak Punya Hak Akses Untuk Melihat Fitur Ini", 403, []);
            }
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
            $user = auth()->user();
            if (!$user->hasPermissionTo(PermissionEnum::MELIHAT_DETAIL_LEASING)) {
                return $this->errorResponse("Tidak Punya Hak Akses Untuk Melihat Fitur Ini", 403, []);
            }
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

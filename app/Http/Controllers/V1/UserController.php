<?php

namespace App\Http\Controllers\V1;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Hash;
use Illuminate\Http\Request;

use function strlen;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'page' => 'required|integer',
            'perPage' => 'required|integer',
            'is_public' => 'boolean'
        ]);

        try {
            $user = auth()->user();

            if (!$user->hasPermissionTo(PermissionEnum::MELIHAT_PENGUNA) && !$request->has('is_public')) {
                return $this->errorResponse("Tidak Punya Hak Akses Untuk Melihat Fitur Ini", 403, []);
            }

            $page = $request->get('page');
            $perPage = $request->get('perPage');
            $search= $request->get('search');

            $findAllUser = User::with(['roles'])->where('deleted_at', null);

            if ($request->has('search') && strlen($request->get('search')) > 0) {
                $findAllUser->where(function ($query) use ($search) {
                    return $query->orWhere('name', 'like', "%$search%")
                        ->orWhere('username', 'LIKE', "%$search%")
                        ->orWhere('email', 'LIKE',"%$search%");
                });
            }

            if ($request->has('sortBy') && $request->has('orderBy')) {
                $findAllUser->orderBy($request->get('orderBy'), $request->get('sortBy'));
            }

            return $this->successResponse("Berhasil Mendapatkan Data User", 200, [
                'items' => $findAllUser->paginate($perPage),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500, []);

        }
    }

    public function create(Request $createUserRequest)
    {
        $createUserRequest->validate([
            'name' => 'required|string',
            'username' => 'required|string',
            'phone' => 'string',
            'email' => 'required|string',
            'password' => 'required|string|min:6',
            'role_id' => 'required|integer'
        ]);
        try {

            $user = auth()->user();

            if (!$user->hasPermissionTo(PermissionEnum::MEMBUAT_PENGUNA)) {
                return $this->errorResponse("Tidak Punya Hak Akses Untuk Melihat Fitur Ini", 403, []);
            }

            $User = new User;

            $User->name = $createUserRequest->get('name');
            $User->username = $createUserRequest->get('username');
            $User->email = $createUserRequest->get(key: 'email');
            $User->password = Hash::make($createUserRequest->get('password'));
            $User->phone = $createUserRequest->get('phone');
            $user->role_id = $createUserRequest->get('role_id');

            if ($User->save()) {
                return $this->successResponse("Berhasil Menambahkan User", 200, [
                    'data' => $User
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
            if (!$user->hasPermissionTo(PermissionEnum::MENGHAPUS_PENGUNA)) {
                return $this->errorResponse("Tidak Punya Hak Akses Untuk Melihat Fitur Ini", 403, []);
            }
            $findUser = User::find($id);
            if (!$findUser) {
                return $this->errorResponse("User Tidak Ditemukan");
            }

            if ($findUser->delete()) {
                return $this->successResponse("Berhasil Menghapus User", 200, [
                    'data' => $findUser
                ]);
            }

        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode(), []);
        }
    }

    public function update(Request $updateUserRequest, $id)
    {
        try {
            $user = auth()->user();
            if (!$user->hasPermissionTo(PermissionEnum::MENGEDIT_PENGUNA)) {
                return $this->errorResponse("Tidak Punya Hak Akses Untuk Melihat Fitur Ini", 403, []);
            }
            $findUser = User::find($id);
            if (!$findUser) {
                return $this->errorResponse("User Tidak Ditemukan");
            }

            $findUser->name = $updateUserRequest->get('name');
            $findUser->username = $updateUserRequest->get('username');
            $findUser->email = $updateUserRequest->get(key: 'email');
            $findUser->password = Hash::make($updateUserRequest->get('password'));
            $findUser->phone = $updateUserRequest->get('phone');
            $findUser->role_id = $updateUserRequest->get('role_id');

            if ($findUser->save()) {
                return $this->successResponse("Berhasil Mengedit User", 200, [
                    'data' => $findUser
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
            if (!$user->hasPermissionTo(PermissionEnum::MELIHAT_DETAIL_PENGUNA)) {
                return $this->errorResponse("Tidak Punya Hak Akses Untuk Melihat Fitur Ini", 403, []);
            }
            $findUser = User::find($id);
            if (!$findUser) {
                return $this->errorResponse("User Tidak Ditemukan");
            }

            return $this->successResponse("Berhasil Mendapatkan Data User", 200, [
                'data' => $findUser
            ]);

        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode(), []);
        }
    }
}

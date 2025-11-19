<?php

namespace App\Http\Controllers\V1;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use function count;
use function in_array;

class RoleController extends Controller
{
  public function index(Request $request)
  {
    try {
      $user = auth()->user();
      if (!$user->hasPermissionTo(PermissionEnum::MELIHAT_ROLE)) {
        return $this->errorResponse("Tidak Punya Hak Akses Untuk Melihat Fitur Ini", 403, []);
      }
      $page = $request->get('page');
      $perPage = $request->get('perPage');

      $allRole = Role::where('deleted_at', null)->paginate($perPage);

      return $this->successResponse("Berhasil Mendapatkan Data Role", 200, [
        'items' => $allRole
      ]);

    } catch (Exception $e) {
      return $this->errorResponse($e->getMessage(), $e->getCode(), []);
    }
  }

  public function create(Request $request)
  {
    $request->validate([
      'name' => 'required|string',
      'permissionIds' => 'required|array'
    ]);

    try {
      $user = auth()->user();
      if (!$user->hasPermissionTo(PermissionEnum::MEMBUAT_ROLE)) {
        return $this->errorResponse("Tidak Punya Hak Akses Untuk Melihat Fitur Ini", 403, []);
      }
      $createRole = Role::create([
        'name' => $request->get('name'),
        'guard_name' => 'web'
      ]);

      $permissions = Permission::whereIn('id', $request->get('permissionIds'))->get();

      if (!$permissions) {
        return $this->errorResponse("Permission Diperlukan", 400, []);
      }

      $createRole->givePermissionTo($permissions);

      if ($createRole->save()) {
        return $this->successResponse("Berhasil Menambahkan Role", 200, []);
      }

      return $this->errorResponse("Gagal Menambahkan Role", 500, []);

    } catch (Exception $e) {
      return $this->errorResponse($e->getMessage(), $e->getCode(), []);
    }
  }

  public function update(Request $request, $id)
  {
    try {
      $user = auth()->user();
      if (!$user->hasPermissionTo(PermissionEnum::MENGEDIT_ROLE)) {
        return $this->errorResponse("Tidak Punya Hak Akses Untuk Melihat Fitur Ini", 403, []);
      }
      $findRole = Role::with(['permissions'])->where('id', $id)->first();
      if (!$findRole) {
        return $this->errorResponse("Role Tidak Ditemukan", 404, []);
      }

      $revokedPermissions = [];
      $newPermissions = [];

      $permissionIds = $request->get('permissionIds');
      $findRole->name = $request->get("name");

      if (count($permissionIds, COUNT_NORMAL) > count($findRole->permissions, COUNT_NORMAL)) {
        foreach ($permissionIds as $permissionId) {
          if (!in_array($permissionId, $findRole->permissions)) {
            $newPermissions[] = Permission::findById($permissionId);
          }
        }

        $findRole->givePermissionTo($newPermissions);

      } else {
        foreach ($findRole->permissions as $permission) {
          if (!in_array($permission->id, $permissionIds)) {
            $revokedPermissions = $permission;
          }
        }

        $findRole->revokePermissionTo($revokedPermissions);
      }

      if ($findRole->save()) {
        return $this->successResponse("Berhasil Mengedit Role", 200, []);
      }

    } catch (Exception $e) {
      return $this->errorResponse($e->getMessage(), $e->getCode(), []);
    }
  }

  public function delete($id)
  {
    try {
      $user = auth()->user();
      if (!$user->hasPermissionTo(PermissionEnum::MENGHAPUS_ROLE)) {
        return $this->errorResponse("Tidak Punya Hak Akses Untuk Melihat Fitur Ini", 403, []);
      }
      $findRole = Role::find($id);
      if (!$findRole) {
        return $this->errorResponse("Role Tidak Ditemukan", 404, []);
      }

    } catch (Exception $e) {
      return $this->errorResponse($e->getMessage(), $e->getCode(), []);
    }
  }

  public function detail($id)
  {
    try {
      $user = auth()->user();
      if (!$user->hasPermissionTo(PermissionEnum::MELIHAT_DETAIL_ROLE)) {
        return $this->errorResponse("Tidak Punya Hak Akses Untuk Melihat Fitur Ini", 403, []);
      }
      $findRole = Role::with(['permissions'])->where('id', $id)->first();
      if (!$findRole) {
        return $this->errorResponse("Role Tidak Ditemukan", 404, []);
      }

      return $this->successResponse('Berhasil Mendapatkan Data Role', 200, [
        'data' => $findRole
      ]);

    } catch (Exception $e) {
      return $this->errorResponse($e->getMessage(), $e->getCode(), []);
    }
  }
}

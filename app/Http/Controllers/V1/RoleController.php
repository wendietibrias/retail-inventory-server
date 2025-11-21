<?php

namespace App\Http\Controllers\V1;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Psr\Http\Client\NetworkExceptionInterface;
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
      $allRole = Role::with(['permissions']);

      if ($request->has('search')) {
        $allRole->where(function ($query) use ($request) {
          $search = $request->get('search');
          return $query->where('name', 'like', "%$search%");
        });
      }

      if ($request->has('sortBy') && $request->has('orderBy')) {
        $allRole->orderBy($request->get('orderBy'), $request->get('sortBy'));
      }

      return $this->successResponse("Berhasil Mendapatkan Data Role", 200, [
        'items' => $allRole->paginate($perPage)
      ]);

    } catch (Exception $e) {
      return $this->errorResponse($e->getMessage(), 500, []);
    } catch (QueryException $eq) {
      return $this->errorResponse($eq->getMessage(), 500, []);
    } catch (NetworkExceptionInterface $nei) {
      return $this->errorResponse($nei->getMessage(), 500, []);
    }
  }

  public function create(Request $request)
  {
    $request->validate([
      'name' => 'required|string',
      'permission_ids' => 'required|array'
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

      $permissions = Permission::whereIn('id', $request->get('permission_ids'))->get();

      if (!$permissions) {
        return $this->errorResponse("Permission Diperlukan", 400, []);
      }

      $createRole->givePermissionTo($permissions);

      if ($createRole->save()) {
        return $this->successResponse("Berhasil Menambahkan Role", 200, []);
      }

      return $this->errorResponse("Gagal Menambahkan Role", 500, []);

    } catch (Exception $e) {
      return $this->errorResponse($e->getMessage(), 500, []);
    } catch (QueryException $eq) {
      return $this->errorResponse($eq->getMessage(), 500, []);
    } catch (NetworkExceptionInterface $nei) {
      return $this->errorResponse($nei->getMessage(), 500, []);
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

      if (count($permissionIds) !== count($findRole->permissions)) {
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
      }

      if ($findRole->save()) {
        return $this->successResponse("Berhasil Mengedit Role", 200, []);
      }

    } catch (Exception $e) {
      return $this->errorResponse($e->getMessage(), 500, []);
    } catch (QueryException $eq) {
      return $this->errorResponse($eq->getMessage(), 500, []);
    } catch (NetworkExceptionInterface $nei) {
      return $this->errorResponse($nei->getMessage(), 500, []);
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
      return $this->errorResponse($e->getMessage(), 500, []);
    } catch (QueryException $eq) {
      return $this->errorResponse($eq->getMessage(), 500, []);
    } catch (NetworkExceptionInterface $nei) {
      return $this->errorResponse($nei->getMessage(), 500, []);
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
      return $this->errorResponse($e->getMessage(), 500, []);
    } catch (QueryException $eq) {
      return $this->errorResponse($eq->getMessage(), 500, []);
    } catch (NetworkExceptionInterface $nei) {
      return $this->errorResponse($nei->getMessage(), 500, []);
    }
  }
}

<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function index(){
        try {
          $permissions = Permission::all();

          return $this->successResponse("Berhasil Mendapatkan Permission", 200,[
            'items'=>$permissions
          ]);

        } catch(Exception $e){
            return $this->errorResponse($e->getMessage(),$e->getCode(),[]);
        }
    }
}
                                                   
<?php 
namespace App\Helper;

class CheckPermissionHelper {
    public static function checkItHasPermission($payload){
        $permission = $payload['permission'];
        $isPublic = $payload['is_public'];
        $user = auth()->user();
        if($user->hasPermissionTo($permission) && $isPublic){
            return true;
        } else if($user->hasPermissionTo($permission) && !$isPublic){
            return true;
        }else{
            return false;
        }
    }
}
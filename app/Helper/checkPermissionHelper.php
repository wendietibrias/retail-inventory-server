<?php 

class CheckPermissionHelper {
    public static function checkItHasPermission($permission,$isPublic){
        $user = auth()->user();
        if($user->hasPermissionTo($permission) && !$isPublic){
            return true;
        } else {
            return false;
        }
    }
}
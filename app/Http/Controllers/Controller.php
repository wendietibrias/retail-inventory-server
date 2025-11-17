<?php

namespace App\Http\Controllers;

abstract class Controller
{
    public function successResponse($message,$statusCode = 200,$data){
        return response()->json([
            'status'=>'success',
            'statusCode'=>$statusCode,
            'message'=>$message,
            'data'=>$data
        ],$statusCode);
    }
    public function errorResponse($message,$statusCode = 500,$data){
          return response()->json([
            'status'=>'success',
            'statusCode'=>$statusCode,
            'message'=>$message,
            'error'=>$data
        ],status: $statusCode);
    }
}

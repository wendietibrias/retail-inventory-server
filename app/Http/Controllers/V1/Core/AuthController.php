<?php

namespace App\Http\Controllers\V1\Core;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use Hash;
use Log;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AuthController extends Controller
{
    public function login(LoginRequest $loginRequest)
    {
        $findUser = User::where('username', $loginRequest->get('username'))->first();

        if (!$findUser) {
            throw new NotFoundHttpException("Penguna Tidak Ditemukan");
        }

        if (!Hash::check($loginRequest->get('password'), $findUser->password)) {
            throw new BadRequestException("Password atau Username tidak sesuai");
        }

        $atExpireTime = now()->addMinutes(config('sanctum.expiration'));

        $token = $findUser->createToken('access_token', [], $atExpireTime)->plainTextToken;

        return $this->successResponse('Berhasil Login', 200, [
            'access_token' => $token,
            'me' => $findUser->except('password'),
            'role' => $findUser->roles()->first(),
            'permissions' => $findUser->getPermissionsViaRoles(),
        ]);

    }

    public function logout(Request $request)
    {
        $user = request()->user();
        $user->tokens()->where('id', $user->currentAccessToken()->id)->delete();
        return $this->successResponse('Berhasil logout', 200, []);
    }

    public function refresh(Request $request)
    {
        $request->user()->tokens()->delete();

        $findUser = auth()->user();

        $atExpireTime = now()->addMinutes(config('sanctum.expiration'));
        $rtExpireTime = now()->addMinutes(config('sanctum.rt_expiration'));

        $token = $findUser->createToken('access_token', [], $atExpireTime)->plainTextToken;
        $refreshToken = $findUser->createToken('refresh_token', [], $rtExpireTime)->plainTextToken;

        return $this->successResponse('Berhasil Login', 200, [
            'accessToken' => $token,
            'refreshToken' => $refreshToken,
            'me' => $findUser->except('password')
        ]);
    }

    public function me()
    {
        $user = auth()->user();
        return $this->successResponse('Berhasil Mendapatkan Penguna', 200, [
            'me' => $user
        ]);
    }
}

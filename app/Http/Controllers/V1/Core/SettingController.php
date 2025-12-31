<?php

namespace App\Http\Controllers\V1;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use Exception;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function detail()
    {
        try {
            $user = auth()->user();
            // if (!$user->hasPermissionTo(PermissionEnum::MENGEDIT_SETTING)) {
            //     return $this->errorResponse("Tidak Ada Hak Untuk Melihat Fitur Ini", 403, []);
            // }
            $findSetting = Setting::where('deleted_at', null)->first();
            return $this->successResponse('Berhasil Mendapatkan Data Setting', 200, [
                'data' => $findSetting
            ]);

        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500, []);
        }
    }

    public function update(Request $request)
    {
        try {   
            $user =auth()->user();
            if (!$user->hasPermissionTo(PermissionEnum::MENGEDIT_SETTING)) {
                return $this->errorResponse("Tidak Ada Hak Untuk Melihat Fitur Ini", 403, []);
            }
            $findSetting = Setting::where('deleted_at', null)->first();
            if (!$findSetting) {
               $findSetting = new Setting;
            }

            $findSetting->night_shift_time = $request->get('night_shift_time');
            $findSetting->morning_shift_time = $request->get('morning_shift_time');
            $findSetting->no_tax_invoice_code = $request->get('no_tax_invoice_code');
            $findSetting->tax_invoice_code = $request->get('tax_invoice_code');

            if ($findSetting->save()) {
                return $this->successResponse("Berhasil Mengupdate Setting", 200, [
                    'data' => $findSetting->fresh()
                ]);
            }

        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500, []);
        }
    }
}

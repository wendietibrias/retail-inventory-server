<?php

namespace App\Enums;
enum ReceiveableStatusEnum: string {
    case DIBUAT = "DIBUAT";
    case LUNAS = "LUNAS";
    case BELUM_LUNAS = "BELUM LUNAS";


    case VOID = "VOID";
}
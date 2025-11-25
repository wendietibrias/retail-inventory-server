<?php

namespace App\Enums;
enum ReceiveableStatusEnum: string {
    case LUNAS = "LUNAS";
    case BELUM_LUNAS = "BELUM LUNAS";

    case DIBAYARKAN_PARSIAL = "DIBAYARKAN PARSIAL";

    case VOID = "VOID";
}
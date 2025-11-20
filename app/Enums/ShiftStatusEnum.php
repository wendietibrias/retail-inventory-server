<?php

namespace App\Enums;

enum ShiftStatusEnum: string {
    case SEDANG_BERLANGSUNG = "SEDANG BERLANGSUNG";
    case BELUM_MULAI ="BELUM DIMULAI";
    CASE SELESAI ="SELESAI";
}
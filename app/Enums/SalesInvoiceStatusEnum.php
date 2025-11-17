<?php

namespace App\Enums;

enum SalesInvoiceStatusEnum: string {
    case DIBUAT = "DIBUAT";
    case BELUM_LUNAS = "BELUM LUNAS";
    case MEMERLUKAN_PERSETUJUAN_PIUTANG = "MEMERLUKAN PERSETUJUAN PIUTANG";
    case DISETUJUI_MENJADI_PIUTANG = "DISETUJUI MENJADI PIUTANG";

    case LUNAS = "LUNAS";
    case VOID = "VOID";
}
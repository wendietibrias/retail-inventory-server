<?php 

namespace App\Enums;

enum StockAdjustmentStatusEnum: string{
    case DIBUAT = "DIBUAT";
    case DISETUJUI = "DISETUJUI";
    case DITOLAK = "DITOLAK";
}
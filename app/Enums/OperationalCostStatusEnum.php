<?php 

namespace App\Enums;

enum OperationalCostStatusEnum: string {
    case MEMERLUKAN_PERSETUJUAN = "MEMERLUKAN PERSETUJUAN";
    case DISETUJUI = "DISETUJUI";
    case DITOLAK = "DITOLAK";
}
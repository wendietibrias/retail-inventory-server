<?php 

namespace App\Enums;

enum OutboundStatusEnum: string {
    case DIBUAT = "DIBUAT";
    case DISETUJUI = "DISETUJUI";
    case DITOLAK = "DITOLAK";
}
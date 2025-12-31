<?php 

namespace App\Enums;

enum InboundStatusEnum: string {
    case DIBUAT = "DIBUAT";
    case DISETUJUI = "DISETUJUI";
    case DITOLAK = "DITOLAK";
}
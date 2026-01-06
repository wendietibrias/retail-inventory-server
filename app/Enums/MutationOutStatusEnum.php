<?php 

namespace App\Enums;

enum MutationOutStatusEnum: string {
    case DIBUAT = "DIBUAT";
    case DISETUJUI = "DISETUJUI";
    case DITOLAK = "DITOLAK";
}
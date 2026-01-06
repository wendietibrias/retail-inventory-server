<?php 

namespace App\Enums;

enum MutationInStatusEnum: string {
    case DIBUAT = "DIBUAT";
    case DISETUJUI = "DISETUJUI";
    case DITOLAK = "DITOLAK";
}
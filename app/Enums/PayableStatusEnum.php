<?php 

namespace App\Enums;

enum PayableStatusEnum: string {
   case MEMERLUKAN_PERSETUJUAN = "MEMERLUKAN PERSETUJUAN";
   case DISETUJUI = "DISETUJUI";
   case DITOLAK = "DITOLAK";
}
<?php 

namespace App\Enums;

enum StockMovementTypeEnum: string { 
    case IN = "IN";
    case OUT = "OUT";
    case ADJUSTMENT = "ADJUSTMENT";
}
<?php 

namespace App\Enums;

enum StockMovementOriginEnum: string { 
   case INBOUND = "INBOUND";
   case OUTBOUND = "OUTBOUND";
   case ADJUSTMENT = "ADJUSTMENT";
   case MUTATION_IN = "MUTATION IN";
   case MUTATION_OUT = "MUTATION OUT";
}
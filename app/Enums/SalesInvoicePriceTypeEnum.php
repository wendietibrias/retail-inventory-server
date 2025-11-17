<?php

namespace App\Enums;

enum SalesInvoicePriceTypeEnum: string {
    case RETAIL = "RETAIL";
    case DEALER = "DEALER";

    case ONLINE = "ONLINE";

    case SHOWCASE ="SHOWCASE";
}
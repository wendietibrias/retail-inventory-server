<?php

namespace App\Enums;

enum NotificationPriorityEnum: string {
    case URGENT = "URGENT";
    case TOP_URGENT = "TOP URGENT";
    case NORMAL = "NORMAL";
}
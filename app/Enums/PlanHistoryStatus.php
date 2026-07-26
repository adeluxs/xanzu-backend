<?php

namespace App\Enums;

enum PlanHistoryStatus: string
{
    case ACTIVE = 'active';
    case PENDING = 'pending';
    case CANCELLED = 'cancelled';
    case EXPIRED = 'expired';
}

<?php

namespace App\Enums;

enum TxnStatus: string
{
    case Success = 'success';
    case Pending = 'pending';
    case Cancelled = 'cancelled';
    case Failed = 'failed';

    case PartiallyPaid = 'partially_paid';

    case Refunded = 'refunded';
}

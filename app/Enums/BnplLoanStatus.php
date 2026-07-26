<?php

namespace App\Enums;

enum BnplLoanStatus: string
{
    case Pending = 'pending';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
    case Overdue = 'overdue';
    case Cancelled = 'cancelled';

    case Processing = 'processing';
}

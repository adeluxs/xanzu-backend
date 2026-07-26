<?php

namespace App\Enums;

enum CardApplicationStatus: string
{
    case Pending = 'pending';
    case OnHold = 'onhold';
    case Approved = 'approved';
    case Rejected = 'rejected';
}

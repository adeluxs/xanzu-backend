<?php

namespace App\Enums;

enum ListingReview: string
{
    case Pending = 'pending';
    case Approved = 'approved';

    case Flagged = 'flagged';
    case Rejected = 'rejected';
}

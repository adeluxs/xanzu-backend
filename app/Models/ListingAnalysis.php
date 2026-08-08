<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ListingAnalysis extends Model
{
    protected $table = 'listing_analysis';

    protected $fillable = [
        'listing_id',
        'event_type',
    ];
}

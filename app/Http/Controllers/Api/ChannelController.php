<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use Illuminate\Http\JsonResponse;

class ChannelController extends Controller
{
    /**
     * Get all active channels
     */
    public function index(): JsonResponse
    {
        $channels = Channel::active()->get(['id', 'name']);

        return response()->json($channels);
    }
}

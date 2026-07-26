<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TestController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $data = [
            'params' => $request->all(),
            'headers' => $request->headers->all(),
        ];

        \Log::info('TestController received request', $data);
        // dd();
    }
}

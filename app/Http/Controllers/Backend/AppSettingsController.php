<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;

class AppSettingsController extends Controller
{
    public function splashScreen()
    {
        return view('backend.setting.app_settings.onboarding_screen');
    }
}

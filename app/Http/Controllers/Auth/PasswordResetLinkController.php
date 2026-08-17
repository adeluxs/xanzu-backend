<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\JsonData;
use App\Traits\NotifyTrait;
use DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Str;

class PasswordResetLinkController extends Controller
{
    use NotifyTrait;

    /**
     * Display the password reset link request view.
     *
     * @return View
     */
    public function create()
    {
        $page = getPageData('forgetpassword');
        $data = JsonData::decodeArray($page?->data);

        return view('frontend::auth.forgot-password', compact('data'));
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @return RedirectResponse
     *
     * @throws ValidationException
     */
    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email', 'exists:users'],
        ]);

        if ($validator->fails()) {
            notify()->error($validator->errors()->first(), 'Error');

            return back()->with('error', __('Email or Username not found!'));
        }

        $token = Str::random(64);

        DB::table('password_resets')->insert([
            'email' => $request->email,
            'token' => $token,
            'created_at' => Date::now(),
        ]);

        $token = route('password.reset', ['token' => $token, 'email' => $request->email]);

        $shortcodes = [
            '[[token]]' => $token,
            '[[site_title]]' => setting('site_title', 'global'),
            '[[site_url]]' => route('home'),
        ];

        $this->sendNotify($request->email, 'user_password_change', 'User', $shortcodes, null, null);
        notify()->success(__('We have e-mailed your password reset link!'));

        return back();

    }
}

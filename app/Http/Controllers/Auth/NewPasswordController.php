<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\User;
use App\Support\JsonData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Fluent;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     *
     * @return View
     */
    public function create(Request $request)
    {
        $page = Page::where('code', 'forgetpassword')->where('locale', app()->getLocale())->where('theme', site_theme())->first();

        if (! $page) {
            $page = Page::where('code', 'forgetpassword')->where('locale', defaultLocale())->where('theme', site_theme())->firstOrFail();
        }
        $data = new Fluent(JsonData::decodeArray($page->data));

        return view('frontend::auth.reset-password', compact('request', 'data'));

    }

    /**
     * Handle an incoming new password request.
     *
     * @return RedirectResponse
     *
     * @throws ValidationException
     */
    public function store(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $updatePassword = DB::table('password_resets')
            ->where([
                'email' => $request->email,
                'token' => $request->token,
            ])
            ->first();

        if (! $updatePassword) {
            notify()->error(__('Invalid token!'), 'Error');

            return to_route('password.request');
        }

        $user = User::where('email', $request->email)
            ->update(['password' => Hash::make($request->password)]);

        DB::table('password_resets')->where(['email' => $request->email])->delete();

        notify()->success(__('Your password has been changed!'), 'Success');

        return to_route('login');

    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     *
     * @return RedirectResponse
     */
    public function store(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(RouteServiceProvider::HOME);
        }

        try {
            $request->user()->sendEmailVerificationNotification();
            notify()->success(__('A new verification link has been sent to the email address you provided during registration.'), 'Success');
        } catch (Throwable $th) {
            // throw $th;
            notify()->error(__('Something went wrong!'), 'Error');
        }

        return back()->with('status', 'verification-link-sent');
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\LoginActivities;
use App\Providers\RouteServiceProvider;
use App\Support\JsonData;
use App\Traits\NotifyTrait;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    use NotifyTrait;

    /**
     * Display the login view.
     *
     * @return View
     */
    public function create()
    {

        $page = getPageData('login');
        $data = JsonData::decodeArray($page?->data);

        return view('frontend::auth.login', compact('data'));
    }

    /**
     * Handle an incoming authentication request.
     *
     * @return RedirectResponse
     */
    public function store(LoginRequest $request)
    {
        $oldTheme = session()->get('site-color-mode');

        $request->authenticate();
        $request->session()->regenerate();
        $user = Auth::user();

        if (setting('otp_verification', 'permission')) {
            $otp = random_int(1000, 9999);
            $shortcodes = [
                '[[otp_code]]' => $otp,
            ];
            $this->sendNotify($user->email, 'otp', 'User', $shortcodes, $user->phone, $user->id);
            $user->update([
                'otp' => $otp,
            ]);
        }

        LoginActivities::add($user->id);
        session()->put('site-color-mode', $oldTheme);

        try {
            \Log::alert('User Logged In: ' . Auth::user()->username . ' from Ip: ' . $request->ip() . ' Country: ' . getLocation()?->name);
        } catch (\Throwable $th) {
            \Log::error('Error logging user in: ' . $th->getMessage());
        }

        return redirect()->intended(RouteServiceProvider::HOME);
    }

    /**
     * Destroy an authenticated session.
     *
     * @return RedirectResponse
     */
    public function destroy(Request $request)
    {
        $user = Auth::guard('web')->user();
        $user->phone_verified = 0;
        $user->save();

        Auth::guard('web')->logout();

        // $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('login');
    }
}

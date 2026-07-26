<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

class SocailLoginController extends Controller
{
    public function redirect(Request $request, $provider)
    {
        abort_unless(in_array($provider, ['google', 'facebook']), 404);
        session()->put('reg_user_type', $request->get('user_type', 'buyer'));
        try {
            return Socialite::driver($provider)->stateless()->redirect();
        } catch (Exception $e) {
            // throw $e;
            notify()->error(__('Something went wrong!'), 'Error');

            return back();
        }
    }

    public function callback(Request $request, $provider)
    {
        abort_unless(in_array($provider, ['google', 'facebook']), 404);
        try {
            $user = Socialite::driver($provider)->stateless()->user();
            $email = $user->email;
            $name = $user->name;
            $lastName = $user->nickname;

            do {
                $username = str($name.'-'.($lastName ? $lastName : str()->random(4)))->lower()->slug();
            } while (User::whereUsername($username)->exists());

            if (User::whereEmail($email)->exists()) {
                auth()->login(User::whereEmail($email)->first());

                return to_route('user.dashboard');
            }
            $newRequest = new Request([
                'first_name' => $name,
                'last_name' => $lastName,
                'gender' => 'male',
                'username' => $username,
                'country' => getLocation()?->name ?? 'Bangladesh',
                'phone' => '1234567890',
                'email' => $email,
                'password' => str()->password(),
                'user_type' => session()->pull('reg_user_type'),
                'from_social' => true,
            ]);

            app('App\Http\Controllers\Auth\RegisteredUserController')->register($newRequest);
            notify()->success(__('Registration successful!'), 'Success');

            return to_route('login');
        } catch (Exception $e) {
            // throw $e;
            notify()->error(__('Something went wrong!'), 'Error');

            return back();
        }
    }
}

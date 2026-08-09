<?php

namespace App\Support\Auth;

use App\Models\User;

final class MobileAuthPayload
{
    public static function make(User $user, string $token): array
    {
        $requiresEmailVerification =
            (bool) setting('email_verification', 'permission')
            && ! $user->hasVerifiedEmail();

        $requiresTwoFactor =
            (bool) setting('fa_verification', 'permission')
            && (bool) $user->two_fa;

        return [
            'token' => $token,
            'token_type' => 'Bearer',

            'email_verified' =>
                $user->hasVerifiedEmail(),

            'requires_email_verification' =>
                $requiresEmailVerification,

            'requires_two_factor' =>
                $requiresTwoFactor,

            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'username' => $user->username,
                'email' => $user->email,
                'phone' => $user->phone,
                'user_type' => $user->user_type,
                'status' => $user->status,
            ],
        ];
    }
}
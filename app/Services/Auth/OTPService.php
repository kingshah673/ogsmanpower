<?php

namespace App\Services\Auth;

use App\Models\User;

class OTPService
{
    /*
    |--------------------------------------------------------------------------
    | Generate OTP
    |--------------------------------------------------------------------------
    */

    public function generate(User $user)
{
    $otp = rand(100000,999999);

    $user->update([

        'otp_code' => $otp,

        'otp_expiry' => now()->addMinutes(10),

        'is_otp_verified' => 0

    ]);

    $user->refresh();

    \Log::info('OTP GENERATED', [

        'user_id' => $user->id,

        'otp_code' => $user->otp_code,

        'otp_expiry' => $user->otp_expiry,

    ]);

    return $otp;
}

    /*
    |--------------------------------------------------------------------------
    | Verify OTP
    |--------------------------------------------------------------------------
    */

    public function verify(
        User $user,
        $otp
    ) {
        
        

        if (
            !$user->otp_code
        ) {
            return false;
        }

        if (
            now()->gt(
                $user->otp_expiry
            )
        ) {
            return false;
        }

        if (
            $user->otp_code != $otp
        ) {
            return false;
        }

        $user->update([

            'is_otp_verified' => 1,

            'otp_code' => null,

            'otp_expiry' => null
        ]);

        return true;
    }
}
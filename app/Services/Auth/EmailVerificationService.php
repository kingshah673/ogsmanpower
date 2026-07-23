<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Mail;

class EmailVerificationService
{
    protected $otp;

    public function __construct(
        OTPService $otp
    ) {

        $this->otp = $otp;
    }

    /*
    |--------------------------------------------------------------------------
    | Send Email OTP
    |--------------------------------------------------------------------------
    */

    public function send(
        User $user
    ) {

        $otp =
            $this->otp->generate(
                $user
            );

        Mail::raw(

            "Your verification code is: {$otp}",

            function ($mail) use ($user) {

                $mail->to(
                    $user->email
                )
                ->subject(
                    'Email Verification Code'
                );
            }
        );

        return true;
    }
}
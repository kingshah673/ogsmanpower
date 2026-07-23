<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Services\WhatsApp\WhatsAppService;

class WhatsAppVerificationService
{
    protected $otp;

    protected $whatsapp;

    public function __construct(

        OTPService $otp,

        WhatsAppService $whatsapp

    ) {

        $this->otp = $otp;

        $this->whatsapp = $whatsapp;
    }

    /*
    |--------------------------------------------------------------------------
    | Send WhatsApp OTP
    |--------------------------------------------------------------------------
    */

    public function send(User $user)
{
    $otp = $this->otp->generate($user);

    \Log::info('WHATSAPP OTP GENERATED', [
        'user_id' => $user->id,
        'phone'   => $user->whatsapp,
        'otp'     => $otp,
    ]);

    $message = "Your verification code is: {$otp}";

    $response = $this->whatsapp->sendMessage(
        $user->whatsapp,
        $message
    );

    \Log::info('WHATSAPP OTP RESPONSE', [
        'status' => $response->status(),
        'body'   => $response->json(),
    ]);

    return true;
}
}
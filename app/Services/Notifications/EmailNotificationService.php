<?php

namespace App\Services\Notifications;

use Illuminate\Support\Facades\Mail;

class EmailNotificationService
{
    /*
    |--------------------------------------------------------------------------
    | Send Email
    |--------------------------------------------------------------------------
    */

    public function send(

        $email,

        $subject,

        $message

    ) {

        Mail::raw(

            $message,

            function ($mail)
            use (
                $email,
                $subject
            ) {

                $mail->to($email)
                    ->subject($subject);
            }
        );

        return true;
    }
}
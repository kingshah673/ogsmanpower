<?php

namespace App\Services\Notifications;

use Illuminate\Support\Facades\Log;

class NotificationService
{
    protected $whatsapp;

    protected $email;

    public function __construct(

        WhatsAppNotificationService $whatsapp,

        EmailNotificationService $email

    ) {

        $this->whatsapp = $whatsapp;

        $this->email = $email;
    }

    /*
    |--------------------------------------------------------------------------
    | SEND NOTIFICATION
    |--------------------------------------------------------------------------
    */

    public function send(

        $user,

        $message,

        $subject = 'Notification',

        $channels = ['database', 'email', 'whatsapp']

    ) {

        try {

            if (!$user) {

                return false;
            }

            /*
            |--------------------------------------------------------------------------
            | DATABASE NOTIFICATION
            |--------------------------------------------------------------------------
            */

            if (

                in_array(
                    'database',
                    $channels
                )

            ) {

                $this->database(

                    $user,

                    $message,

                    $subject
                );
            }

            /*
            |--------------------------------------------------------------------------
            | EMAIL NOTIFICATION
            |--------------------------------------------------------------------------
            */

            if (

                in_array(
                    'email',
                    $channels
                )

                &&

                !empty(
                    $user->email
                )

            ) {

                $this->email(

                    $user,

                    $subject,

                    $message
                );
            }

            /*
            |--------------------------------------------------------------------------
            | WHATSAPP NOTIFICATION
            |--------------------------------------------------------------------------
            */

            if (

                in_array(
                    'whatsapp',
                    $channels
                )

                &&

                !empty(
                    $user->whatsapp
                )

            ) {

                $this->whatsapp(

                    $user,

                    $message
                );
            }

            return true;

        } catch (\Exception $e) {

            Log::error(

                'Notification Error: '

                .

                $e->getMessage()
            );

            return false;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DATABASE NOTIFICATION
    |--------------------------------------------------------------------------
    */

    public function database(

        $user,

        $message,

        $subject = 'Notification'

    ) {

        try {

            if (

                method_exists(
                    $user,
                    'notify'
                )

            ) {

                $user->notify(

                    new \App\Notifications\GeneralNotification(

                        $message,

                        $subject
                    )
                );
            }

        } catch (\Exception $e) {

            Log::error(

                'Database Notification Error: '

                .

                $e->getMessage()
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | EMAIL NOTIFICATION
    |--------------------------------------------------------------------------
    */

    public function email(

        $user,

        $subject,

        $message

    ) {

        try {

            $this->email
                ->send(

                    $user->email,

                    $subject,

                    $message
                );

        } catch (\Exception $e) {

            Log::error(

                'Email Notification Error: '

                .

                $e->getMessage()
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | WHATSAPP NOTIFICATION
    |--------------------------------------------------------------------------
    */

    public function whatsapp(

        $user,

        $message

    ) {

        try {

            $this->whatsapp
                ->send(

                    $user->whatsapp,

                    $message
                );

        } catch (\Exception $e) {

            Log::error(

                'WhatsApp Notification Error: '

                .

                $e->getMessage()
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SEND ONLY EMAIL
    |--------------------------------------------------------------------------
    */

    public function sendEmail(

        $user,

        $subject,

        $message

    ) {

        return $this->send(

            $user,

            $message,

            $subject,

            ['email']
        );
    }

    /*
    |--------------------------------------------------------------------------
    | SEND ONLY WHATSAPP
    |--------------------------------------------------------------------------
    */

    public function sendWhatsApp(

        $user,

        $message

    ) {

        return $this->send(

            $user,

            $message,

            'WhatsApp Notification',

            ['whatsapp']
        );
    }

    /*
    |--------------------------------------------------------------------------
    | SEND ONLY DATABASE
    |--------------------------------------------------------------------------
    */

    public function sendDatabase(

        $user,

        $message,

        $subject = 'Notification'

    ) {

        return $this->send(

            $user,

            $message,

            $subject,

            ['database']
        );
    }
}
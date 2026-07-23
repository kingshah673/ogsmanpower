<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class WhatsAppMediaService
{
    /*
    |--------------------------------------------------------------------------
    | Download Media
    |--------------------------------------------------------------------------
    */

    public function download(
        $mediaId
    ) {

        /*
        |--------------------------------------------------------------------------
        | GET MEDIA URL
        |--------------------------------------------------------------------------
        */

        $response =
            Http::withToken(

                env('WHATSAPP_TOKEN')

            )->get(

                env('WHATSAPP_API')
                .
                '/'
                .
                $mediaId
            );

        $json =
            $response->json();

        $url =
            $json['url']
            ?? null;

        if (!$url) {

            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | DOWNLOAD FILE
        |--------------------------------------------------------------------------
        */

        $file =
            Http::withToken(

                env('WHATSAPP_TOKEN')

            )->get($url);

        $content =
            $file->body();

        /*
        |--------------------------------------------------------------------------
        | FILE NAME
        |--------------------------------------------------------------------------
        */

        $name =

            'whatsapp_'

            .

            time()

            .

            '.bin';

        /*
        |--------------------------------------------------------------------------
        | STORE FILE
        |--------------------------------------------------------------------------
        */

        $path =
            'whatsapp/'
            .
            $name;

        Storage::disk('public')
            ->put(
                $path,
                $content
            );

        return $path;
    }
}
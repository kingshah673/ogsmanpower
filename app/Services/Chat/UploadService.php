<?php

namespace App\Services\Chat;

use Illuminate\Support\Facades\Storage;

class UploadService
{
    /*
    |--------------------------------------------------------------------------
    | Upload File
    |--------------------------------------------------------------------------
    */

    public function upload(
        $file,
        $folder = 'chatbot'
    ) {

        if (!$file) {

            return null;
        }

        $name =

            time()

            .

            '_'

            .

            preg_replace(

                '/[^A-Za-z0-9\.\-_]/',

                '',

                $file->getClientOriginalName()

            );

        return $file->storeAs(

            $folder,

            $name,

            'public'
        );
    }
}